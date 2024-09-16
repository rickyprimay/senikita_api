<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            font-weight: bold;
        }
        .payment-instructions {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice</h1>
        <p>Hi {{ $details['name'] }},</p>
        <p>Thank you for your order. Here are the details:</p>
    </div>
    
    <div class="invoice-details">
        <p><strong>Invoice Number:</strong> {{ $details['invoice_number'] }}</p>
        <p><strong>Total Price:</strong> {{ formatCurrency($details['price']) }}</p>
        <p><strong>Due Date:</strong> {{ $details['due_date'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details['products'] as $product)
            <tr>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['qty'] }}</td>
                <td>{{ formatCurrency($product['price']) }}</td>
            </tr>
            @endforeach
            <tr>
                <td>Subtotal</td>
                <td></td>
                <td>{{ formatCurrency($details['price']) }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td></td>
                <td>{{ formatCurrency($details['feeAdmin']) }}</td>
            </tr>
            <tr>
                <td>Ongkir</td>
                <td></td>
                <td>{{ formatCurrency($details['ongkir']) }}</td>
            </tr>
            <tr class="total">
                <td>Total Due</td>
                <td></td>
                <td>{{ formatCurrency($details['total_price']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Thank you for your transactions!</p>
    </div>
</body>
</html>
