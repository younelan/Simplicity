<h1 class="widgetheader">{{Please Login}}</h1>
<table class="loginform">
  <form method="POST" action="">
  <input type="hidden" name="csrf_token" value="{{csrf_token}}" />
  <tr>
    <td><label>{{Login}}</label></td>
    <td><input type="text" name="login" /></td>
  </tr>
  <tr>
    <td><label>{{Password}}</label></td>
    <td><input type="password" name="password" /></td>
  </tr>
  <tr class=trailer><td colspan=2>{{trailer}}</td></tr>
  <tr>
    <td colspan=2 align=right><button type="submit">{{Connect}}</button></td>
  </tr>
  </form>
</table>
