<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Login</title>
</head>
<body>
    <h2>User LoggedIn</h2>
    <p>A user has login on your site:</p>

    <ul>
        <li><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
    </ul>

    <p>Thank you for using our site!</p>
</body>
</html>
