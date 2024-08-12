Here's a detailed and structured `README.md` file for the Braspress integration project:

---

# Braspress PHP Client

This PHP client library allows you to interact with the Braspress API for freight quotation and tracking services. The library provides a convenient way to access Braspress's services, including calculating shipping costs and tracking shipments, with support for both rodoviário (road) and aéreo (air) modalities.

## Installation

To install this library, you can use Composer:

```bash
composer require pedroquezado/braspress
```

## Usage

### Initialization

To start using the Braspress client, first, initialize the `BraspressCliente` class with your credentials.

```php
require 'vendor/autoload.php';

use PedroQuezado\Code\Braspress\BraspressCliente;

$cliente = new BraspressCliente('your_braspress_username', 'your_braspress_password');
```

### Adding Products

Before performing a freight quotation, you need to add products (volumes) to the client:

```php
$cliente->inserirProduto(5.5, [
    'comprimento' => 0.67,
    'largura' => 0.67,
    'altura' => 0.46
]);

$cliente->inserirProduto(2.3, [
    'comprimento' => 0.45,
    'largura' => 0.30,
    'altura' => 0.20
]);
```

Each call to `inserirProduto` adds a new volume to the list of products to be included in the freight quotation.

### Performing Freight Quotation

You can perform a freight quotation for either or both modalities (road and air):

```php
try {
    $dadosCotacao = [
        'cnpjRemetente' => '12345678000100',
        'cnpjDestinatario' => '09876543210001',
        'tipoFrete' => '1', // 1 for CIF, 2 for FOB
        'cepOrigem' => '12345000',
        'cepDestino' => '54321000',
        'vlrMercadoria' => 500.00
    ];

    $resultados = $cliente->realizarCotacao($dadosCotacao, 'json', ['R', 'A']);

    print_r($resultados);
} catch (\PedroQuezado\Code\Braspress\BraspressClienteException $e) {
    echo 'Erro: ' . $e->getMessage();
}
```

The `realizarCotacao` method performs the freight quotation for the specified modality (`R` for road, `A` for air, or both). The results are returned as an associative array, separated by modality.

### Example Response

```php
Array
(
    [Rodoviario] => Array
        (
            [id] => 274407950
            [prazo] => 5
            [totalFrete] => 73.02
        )

    [Aereo] => Array
        (
            [id] => 274408248
            [prazo] => 2
            [totalFrete] => 631.38
        )
)
```

### Error Handling

Errors are handled via exceptions. If an error occurs during the API request, a `BraspressClienteException` is thrown:

```php
try {
    // API call
} catch (\PedroQuezado\Code\Braspress\BraspressClienteException $e) {
    echo "Erro: " . $e->getMessage();
}
```

## Advanced Features

### Setting Modalities

You can set the freight modalities when calling the `realizarCotacao` method. Pass an array with `'R'` and/or `'A'` to get quotes for road and/or air modalities.

### Handling Responses

The `realizarCotacao` method returns the result as an associative array. The keys `Rodoviario` and `Aereo` contain the corresponding results.

## Documentation

For more information, please refer to the official Braspress API documentation:

- [Braspress API Documentation](https://api.braspress.com/home)

## License

This library is open-source and available under the MIT license. See the LICENSE file for more information.

## Contributions

Contributions are welcome! Please submit a pull request or open an issue to contribute to this project.
