

<form method="post">
@csrf

<input type="hidden" name="id" value="{{ $user->email}}">

<input type="password" name="password" id="" placeholder="password">

<br><br>
<input type="password" name="password_confirmation" id="" placeholder="Confirm password">
<br><br>
<input type="submit">

</form>