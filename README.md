<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).



---------------------------------------------------------------------------------------------------------


Mobile Running: 
- on the phone open the developer options (wireless debugging)
- ipconfig to check to IPv4 address & copy the port
- & "C:\Users\jc suan\AppData\Local\Android\Sdk\platform-tools\adb.exe" connect 192.168.137.209:38637 (use the phone ip address from developer options to make the laptop and phone connected to each other)
- flutter devices to confirm if the ip address exist
- flutter run then click or choose the phone ip address
- r to hot reload
- R to hot restart
- q to exit development


Web Running:
- php artisan serve to run the web server (localhost:8000 for accessing MFA office 365)
- php artisan reverb:start to make it live viewing (no refresh needed for some parts)
- php artisan serve --host=0.0.0.0 --port=8000 (to connect to mobile)


Web to Email form for registering as reporter:
- php artisan serve --host=0.0.0.0 --port=8000

For alerts locally: php artisan schedule:work (or run php artisan maintenance:check-semester-inspections).

in the system in every signage make everything to be center of the fullname always overlary, even its signed on the side when in signing stage and add a height of the signage area digital signature like 10px or above

also for the toggle switch Brands, UOM, Categories, Sub Categories how to make it will be move in the sidebar like on the section of file maintenance that it will collapsable too 

the modals backdrop bg being cut

in the Maintenance Personnel side is there already a feature where the maintenance personnel set a semestral for checking all equipment

in the system for all the view button that is solo in the action column to be like this design, like same on the maintenance personnel system log walk in report module table

as i notice, since we now implemented the multi role for each person depends on the admin decision if she'll assign a primary role and addiotional role for that person, my problem or question need to solve is how do we handle that in loggin in should we change it from role base to email like instead of purchaser.demo we make it alven.demo@... since we now depends on multi role on persons not just one but still have primary role for just that they still have main job or role, but upon loggin in should we change to depends base on email not role?

for the log in on mobile since in the web system we implemented a primary role and additional role can we change that in log in we also adopt the primary role and additional role like its already given that whoever has primary role of maintenance role already can login, but what about those who has maintenance personnel role but just an additional role right?

and also in the web system theres a semestral campaign of checking all equipments either entire campus or what, should we put that in a mobile too about updating on-site for equipments status during checking of all equipments so that the maintenance personnel wont need to go back to computer or laptop first before can update the statuses or findings of equipments

on developing a app for the codefest competition how to use the recycler viewer template in android developers or on https://developer.android.com/

-------------------------------------------------------------------------------------------------------

Those accounts on the Microsoft picker come from your Entra/Azure tenant — I'll confirm how PaAyo matches them so you know what to change where.

Yes — those emails on “Pick an account” are Microsoft Entra ID (Azure AD) users, not PaAyo settings.

1. Microsoft side (what the picker shows)
In Azure Portal / Microsoft Entra admin center:

Identity → Users (or Entra → Users)

There you create/edit users like:

purchaser.demo@…onmicrosoft.com → change to person emails, e.g. alven.demo@… or a real STI mailbox
Or create new person users and stop using role-named accounts
Password and MFA are also managed there (or via Microsoft 365 admin).

Your app only sends people to Microsoft login; it does not invent that account list.

2. PaAyo side (must match)
After Microsoft signs you in, PaAyo looks up:

users_table.user_email_address = Microsoft email (case-insensitive)

So update emails in Admin → Users (or DB/seeders) to the same addresses as in Entra. If Azure says alven@… but PaAyo still has purchaser.demo@…, login fails with “not registered.”

Practical move to person-based
Place	What to do
Azure / Entra Users
Person accounts (email + password + MFA)
PaAyo users
Same email on each person; primary + extra roles in PaAyo
Azure App Registration
Usually leave as-is (client ID / redirect) — that’s the app, not the user emails
Short answer: change sign-in emails in Azure/Entra Users, then mirror those emails in PaAyo user records.