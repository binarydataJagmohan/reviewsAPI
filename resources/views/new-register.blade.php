<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New User Registration</title>
</head>
<body>
    <h2>New User Registration</h2>
    <p>A new user has registered on your site:</p>

    <ul>
        <li><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
    </ul>

    <p>Thank you for using our site!</p>
</body>
</html>
