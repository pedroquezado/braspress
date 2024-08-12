<?php

namespace PedroQuezado\Code\Braspress;

/**
 * Classe responsável por integrar com a API da Braspress para realizar cotações de frete.
 */
class BraspressCliente
{
    /**
     * @var string Usuário da API da Braspress.
     */
    private $usuario;

    /**
     * @var string Senha da API da Braspress.
     */
    private $senha;

    /**
     * @var string URL base da API da Braspress, dependendo do ambiente (produção ou homologação).
     */
    private $baseUrl;

    /**
     * @var string Cabeçalho de autorização para a API da Braspress, baseado em Basic Auth.
     */
    private $authorizationHeader;

    /**
     * @var array Lista de volumes a serem cotados.
     */
    private $volumes = [];

    /**
     * @var int Quantidade total de volumes inseridos.
     */
    private $quantidadeVolumes = 0;

    /**
     * Construtor da classe. Define usuário, senha e URL base dependendo do ambiente.
     *
     * @param string $usuario Usuário da API.
     * @param string $senha Senha da API.
     * @param bool $producao Se true, usa a URL de produção; caso contrário, usa a URL de homologação.
     */
    public function __construct($usuario, $senha, $producao = true)
    {
        $this->usuario = $this->validarUsuario($usuario);
        $this->senha = $this->validarSenha($senha);
        $this->baseUrl = $producao ? "https://api.braspress.com" : "https://api-homologacao.braspress.com";
        $this->authorizationHeader = "Authorization: Basic " . base64_encode("{$this->usuario}:{$this->senha}");
    }

    /**
     * Valida o usuário da API.
     *
     * @param string $usuario
     * @return string
     * @throws BraspressClienteException Se o usuário for inválido.
     */
    private function validarUsuario($usuario)
    {
        if (empty($usuario) || !is_string($usuario)) {
            throw new BraspressClienteException("Usuário inválido.");
        }
        return $usuario;
    }

    /**
     * Valida a senha da API.
     *
     * @param string $senha
     * @return string
     * @throws BraspressClienteException Se a senha for inválida.
     */
    private function validarSenha($senha)
    {
        if (empty($senha) || !is_string($senha)) {
            throw new BraspressClienteException("Senha inválida.");
        }
        return $senha;
    }

    /**
     * Insere um produto na lista de volumes a serem cotados.
     *
     * @param float $peso Peso do volume.
     * @param array $dimensoes Dimensões do volume (comprimento, largura, altura).
     * @throws BraspressClienteException Se o peso ou as dimensões forem inválidos.
     */
    public function inserirProduto($peso, array $dimensoes)
    {
        $this->validarPeso($peso);
        $this->validarDimensoes($dimensoes);

        $this->volumes[] = [
            "comprimento" => $dimensoes['comprimento'],
            "largura" => $dimensoes['largura'],
            "altura" => $dimensoes['altura'],
            "peso" => $peso
        ];
        $this->quantidadeVolumes++;
    }

    /**
     * Valida o peso do volume.
     *
     * @param float $peso
     * @throws BraspressClienteException Se o peso for inválido.
     */
    private function validarPeso($peso)
    {
        if (!is_numeric($peso) || $peso <= 0) {
            throw new BraspressClienteException("Peso inválido.");
        }
    }

    /**
     * Valida as dimensões do volume.
     *
     * @param array $dimensoes
     * @throws BraspressClienteException Se qualquer dimensão for inválida.
     */
    private function validarDimensoes(array $dimensoes)
    {
        if (empty($dimensoes['comprimento']) || empty($dimensoes['largura']) || empty($dimensoes['altura'])) {
            throw new BraspressClienteException("Dimensões inválidas.");
        }

        foreach (['comprimento', 'largura', 'altura'] as $dimensao) {
            if (!is_numeric($dimensoes[$dimensao]) || $dimensoes[$dimensao] <= 0) {
                throw new BraspressClienteException("Dimensão $dimensao inválida.");
            }
        }
    }

    /**
     * Calcula o peso total de todos os volumes inseridos.
     *
     * @return float Peso total.
     */
    private function calcularPesoTotal()
    {
        return array_reduce($this->volumes, function($total, $volume) {
            return $total + $volume['peso'];
        }, 0);
    }

    /**
     * Realiza a cotação com a API da Braspress.
     *
     * @param array $dadosCotacao Dados necessários para a cotação.
     * @param string $returnType Tipo de retorno esperado (json ou xml).
     * @param string|array $modal Modalidade de transporte ('R' para rodoviário, 'A' para aéreo, ou ambos).
     * @return array Resultado da cotação para cada modal.
     * @throws BraspressClienteException Se ocorrer um erro na cotação ou na API.
     */
    public function realizarCotacao(array $dadosCotacao, $returnType = 'json', $modal = 'R')
    {
        $resultados = [];
        $modalidades = is_array($modal) ? $modal : [$modal];

        foreach ($modalidades as $modalidade) {
            $dadosCotacao['modal'] = $this->validarModal($modalidade);
            $dadosCotacao['peso'] = $this->calcularPesoTotal();
            $dadosCotacao['volumes'] = $this->quantidadeVolumes;
            $dadosCotacao['cubagem'] = array_map(function($volume) {
                return [
                    "comprimento" => $volume['comprimento'],
                    "largura" => $volume['largura'],
                    "altura" => $volume['altura'],
                    "volumes" => 1
                ];
            }, $this->volumes);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "{$this->baseUrl}/v1/cotacao/calcular/{$returnType}");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosCotacao));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                $this->authorizationHeader
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new BraspressClienteException('Erro ao realizar cotação: ' . curl_error($ch));
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode != 200) {
                throw new BraspressClienteException("Erro ao realizar cotação. HTTP Code: $httpCode. Response: $response", $httpCode, $response);
            }

            if (is_string($response)) {
				$resultado = json_decode($response, true);
				$resultados[$modalidade === 'R' ? 'Rodoviario' : 'Aereo'] = $resultado;
			} else {
				throw new BraspressClienteException('Erro ao consultar prazo: resposta inválida recebida.');
			}
        }

        return $resultados;
    }

    /**
     * Valida a modalidade de transporte.
     *
     * @param string $modal
     * @return string Modalidade validada.
     * @throws BraspressClienteException Se a modalidade for inválida.
     */
    private function validarModal($modal)
    {
        $modalidadesValidas = ['R', 'A'];
        if (!in_array($modal, $modalidadesValidas)) {
            throw new BraspressClienteException("Modalidade inválida: $modal.");
        }
        return $modal;
    }
}
