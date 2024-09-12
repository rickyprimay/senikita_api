<!DOCTYPE html>
<html>
<head>
    <title>Payment Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333333;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dddddd;
        }
        .email-footer {
            color: #888888;
            font-size: 12px;
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="email-container">
    <p>Dear {{ $details['name'] }},</p>

    <p>Here’s another reminder that we haven’t yet received payment for invoice <strong>{{ $details['invoice_number'] }}</strong> for the following products: <strong>{{ $details['product_names'] }}</strong>. The full amount <strong>Rp. {{ number_format($details['price'], 2) }}</strong> was due on <strong>{{ $details['due_date'] }}</strong>.</p>

    <p>Could you please make this payment as soon as possible? Let me know if you’re having any problems.</p>

    <p><a href="{{ $details['invoice_url'] }}" class="button">Pay Invoice</a></p>

    <p>Best regards,<br>{{ $details['sender_name'] }}</p>

    <footer class="email-footer">
        <p>© {{ date('Y') }} SeniKita.id. All Rights Reserved.</p>
    </footer>
</div>

</body>
</html>
