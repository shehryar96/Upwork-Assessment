<!DOCTYPE html>
<html>
<head>
    <title>Upwork TesT</title>
</head>
<body>
    <h3>Hi, {{ $details['name'] }}</h3>
    <p>You've been invited to UpworkTest kindly Signup at the following link</p>
    <p>{{ route('usersignup',encrypt($details['id'] )) }}</p></p>
    <p>Thank you</p>
</body>
</html>