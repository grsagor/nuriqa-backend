<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #9E7676;
            color: #FAF7F3;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #9E7676;
            margin-bottom: 5px;
        }
        .field-value {
            color: #333;
        }
        .message-box {
            background-color: #fff;
            padding: 15px;
            border-left: 4px solid #9E7676;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Contact Form Submission</h1>
    </div>
    <div class="content">
        <div class="field">
            <div class="field-label">Name:</div>
            <div class="field-value">{{ $firstName }} {{ $lastName }}</div>
        </div>

        <div class="field">
            <div class="field-label">Email:</div>
            <div class="field-value"><a href="mailto:{{ $email }}">{{ $email }}</a></div>
        </div>

        <div class="field">
            <div class="field-label">Phone:</div>
            <div class="field-value"><a href="tel:{{ $phone }}">{{ $phone }}</a></div>
        </div>

        <div class="field">
            <div class="field-label">Subject:</div>
            <div class="field-value">{{ $subject }}</div>
        </div>

        @if(!empty($contactMessage))
        <div class="field">
            <div class="field-label">Message:</div>
            <div class="message-box">
                {{ $contactMessage }}
            </div>
        </div>
        @endif
    </div>
</body>
</html>
