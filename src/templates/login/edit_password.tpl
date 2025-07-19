<h1 class="widgetheader">{{Update Password}}</h1>
<form method="POST" action="?action=change_password">
  <input type="hidden" name="csrf_token" value="{{csrf_token}}" />
  <table class="loginform">
  <tr>
    <td><label>{{Current_Password}}</label></td>
    <td><input type="password" name="current_password" /></td>
  </tr>
  <tr>
    <td><label>{{New_Password}}</label></td>
    <td><input type="password" name="new_password" /></td>
  </tr>
  <tr>
    <td><label>{{Confirm_Password}}</label></td>
    <td><input type="password" name="confirm_password" /></td>
  </tr>
  <tr>
    <td colspan=2 align=right>
      <button type="submit">{{Update_Password}}</button>
    </td>
  </tr>
  <tr class=trailer><td colspan=2>{{trailer}}</td></tr>
  </table>
</form>

