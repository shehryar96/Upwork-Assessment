<!DOCTYPE html>
<html>
<head>
    <title>UpworkTesT</title>
</head>
<body>
    <h3>Hi, {{ $details['name'] }}</h3>
    <p>Here the OTP : {{ $details['otp'] }}</p>
    <p>Verify on below link</p>
    <p>{{ route('verifyUser',encrypt($details['id'] )) }}</p></p>
    <p>Thank you</p>
</body>
</html>