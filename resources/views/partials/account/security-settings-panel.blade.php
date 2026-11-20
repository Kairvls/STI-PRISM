{{--
  Security settings UI for Office 365 / Microsoft Entra sign-in.
  Expects: $user
--}}
@include('partials.account.microsoft-security-panel', ['user' => $user ?? auth()->user()])
