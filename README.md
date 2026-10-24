1<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

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

Mobile vs staff login (local):
- Staff / Office 365: open http://localhost:8000 on the PC. MICROSOFT_REDIRECT_URI must stay http://localhost:8000/auth/microsoft/callback (Azure only allows HTTPS or http://localhost for Web redirects).
- Phone on same Wi-Fi: use http://YOUR_LAN_IP:8000 for reporter only (Make Report, registration, chatbot). Do not use Staff Sign In on the phone over a LAN IP — Azure will not accept http://192.168.x.x as a redirect URI. For real mobile staff login later, use an HTTPS tunnel (ngrok) or a deployed HTTPS host.


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


for the maintenance system about the inventory of equipments how to solve this that earlier during the interview our proof said that the inventory of equipments need to connect it with the procurement like theres a basis of the equipments been added on the system not just creating or adding equipments randomly just like rr where the equipments being purchase happens to be delivered or backorder so only equipments that been purchased to a basis when adding equipment but help me think cause the real equipment being purchase happens in rr but our proof said it should be basis on the purchase order where for example 1 purchase order has many different equipment like 10 mouse 10 keyboard etc but for me it should be basis or rr right since rr records what was actually delivered and received that theres a basis or guide at the top of modal when adding equipment so the monitoring and managing are connected from equipment replacement to procurement then from procurement to adding equipment and also for the details of equipment proof said we need to add also the time and date indicator when we add it in inventory, when we deploy it in every department or room then when we disposed it then when its been bought then whos the supplier of that corresponding or specific equipment then other many ideas not just on equipment details but think out of the box for better monitoring and managing of equipment like also when it been delivered when its been fix or replacement like more ideas 

Simple rule to defend in audit
RR second count confirms delivery. Inventory stocking is a system import from that RR, not a second manual purchase entry.

for the report or equipment that has report already since it can be reported again then that equipment or report that has reported that equipment and a collab with equipment that has not yet report then in the maintenance make the new reports always at the top like stock even that report only shows or has equipment report that has already has report

If you later want real desks in offices, a lighter option than the full desk editor is to add office zones like "Desk 1" and "Desk 2" to the zone list for Office-type rooms. The next step (4) connects this to semestral inspections and adds "My Equipment" to the reporter portal.

in the admin system make the procurement shows in it sidebar to not go to frist to purchaser portal before can perform procurement workflow, and already include also in admin that the admin can monitor and manage the reporters, 

in the admin system make the procurement shows it in sidebar to not to go first to purchaser portal before can perform procurement workflow, and include also in admin that the admin can monitor and manage the reporters tab/list, reporters approvals, personnel directory, can add equipment(very optional), can view equipment in stock and equipment been deployed and disposed, can monitor semestral inspection or checking of equipments, property assignment/department like that

since theres an urgency for ris put the urgent ris always on the first list of table still maintain stock and when its from the president approval not direct approval of the admin change the status wording make it easy to understand that is from the president and need signing about issued by to send back to purchaser not just "pending administrator review"

limit the signature saved make it per account cause right now for example one person has addtional roles like his primary role is purchaser theres already save signature from the other purchaser even in his account he doesn't has saved any signature yet and the other scenario for example person 1 purchaser additional role is accounting in the accounting portal of it also theres already signature in his accounting portal even he dont already has saved any signature yet maybe its from the other accounting signature, the point is about confidentiality of signature must be secure to avoid others using your signature

is this already implemented?

Request for Check: any back order blocks the Receiving Report completely. You can't receive the 5 mice that did arrive.

Cash Advance: there's no block. The Purchaser can buy from several suppliers.






1. How the new supplier gets paid on Request for Check. The check was issued to the original supplier, and Request for Check has no liquidation. When the refund comes back and you buy from a new supplier, how is that supplier paid?
A. From the refunded money, with the cash difference remark sent to Accounting.
B. A new Request for Check for the new supplier.
C. Just recorded on the back order, and Accounting handles it outside the system.

answer: the request for check will not assign another supplier its just one supplier the one first or original supplier only if it cash advance has or can set multiple supplier

2. RR row limit. The RR form and printed paper currently hold 9 rows. Adding replacement rows could go past that. Should I:
raise the limit and let the printed form continue on a second page, or
keep 9 and block adding more rows?


u

make this parth of the

for the RIS, ATPs, RR make the rows of form of it that i can add multiple more rows (but i think max it to 50 rows minimum 1 row) when creating or editing ris, atp, rr and can delete rows not just the data inside but the literally the rows and its data and for the RIS use 11 rows default, for the ATP use 8 rows default, and for the RR use 9 rows default 


Yes, the current setup is correct, and keeping separate back-order IDs for the missing and damaged quantities makes sense, even though they come from the same RR line. The two problems usually get resolved in different ways:

Missing (6 pcs): the supplier still owes you those items. They might deliver later ("Waiting for restock"), refund you, or you buy them from another supplier.
Damaged (1 pc): you already have that item, but it's broken. Usually it's returned to the supplier and exchanged for a new one, or refunded.
Because each problem can end differently, each back order needs its own status, reason, replacement or refund record, and second count. For example, the supplier might swap the damaged keyboard tomorrow while the 6 missing ones stay "Waiting for restock" for weeks. With one combined ID, you couldn't mark the damaged one as done while the missing ones are still open.

Many procurement systems handle it the same way: undelivered items are a back order, while returned damaged items get a separate exchange record.

Nothing needs to change. The only thing to remember is that Received counts every piece that arrived, including damaged ones. If 5 arrived and 1 is broken, enter Received 5 and Damaged 1, and the system works out 5 missing plus 1 damaged.

!M3Dc4meT#

How to justify it to the panel: it's separation of duties. The Super Admin manages the system (accounts, roles, settings, audit logs). Day-to-day work belongs to the role that does it: Purchaser for procurement, Maintenance for inventory and reporters, School Administrator for RIS approval and monitoring.





procurement request viewing of 3 latest procurement requests and maybe theres a buttton for each 3 latest procurement ris to open a modal and viewing to action it

in the purchaser system for the ris if it from replacement requests make the item from replacement requests cant be overwrite right? if it only if fill in another row or from create ris form, am i right?

on the school administrator system side for the dashboard module what we can put in the dashboard since the dashboard means easy access to all/everything or thats the part where you can see very important pieces



DONE — Reporter emails + spam alarm: after each submit, email `reporter_email_address` (ticket, time, items); email again on every Maintenance status change; if same employee ID submits 5+ reports in 1 hour, alert Maintenance (notification + daily reminder attention).

on the administator side theres some module or form or table put a color of * and status and buttons same on other system side

For your defense

You could say: "A room is where the equipment physically is. A department is the office that owns it or is accountable for it. Equipment always has a room. It can also be charged to a department or issued to a person, and that's for accountability, not location."

A department is an office, not a place. A room says where equipment physically is; a department says which office owns it and is accountable for it. Equipment always has a room, and can also be charged to a department or issued to a person.

should we add equipment in that department also or what since its just person in that department or person that has equipment and belong to that department


the traditional workflow is the Purchaser first create Canvas + RIS then submit to School Administrator for approval then the School Administrator approve it or either submit to President for approval then go back to the Purchaser the approve Canvas + RIS then that approved Canvas + RIS will be pass to School Supply/Material Officer to have a base when making an ATP then that canvas + ris and atp will be submitted to school administrator by School Supply/Material Officer for approval the reason that ATP has canvas + ris is for reference of ATP cause that ATP is about the canvas + RIS then after that approved ATP by School administrator the School Supply/ATP pass/submit now back it to the Purchaser the already approved canvas + RIS and the approved ATP then the Purchaser start making the RFC base on the approved canvas + ris and atp then that RFC then before that the RFC form is in the Accounting the Purchaser will personally get that then make RFC with the attach of canvas + ris and atp to that rfc submitted to school administrator by the Purchaser for the approval then after the approved RFC now we have approved canvas + ris, atp, rfc and that will be submitted to Accounting by Purchaser then the Accounting starts making the Disbursement Voucher form base on the approved canvas + ris, atp, rfc (remember every form needs basis on the approved form like canvas + ris, atp, rfc and soon cause each all connected to each other) then that Disbursement Voucher now has with attach approved canvas + ris, atp, rfc to the school administrator then after approval by the school administrator it will be pass back or return to Accounting then the accounting submit now the approved Disbursement Voucher, with already approved canvas + ris, atp, rfc to the President then the president approved the Disbursement Voucher (with already approved canvas + ris, atp, rfc) too it will be pass back or return again to the Accounting then now the accounting will start releasing the cheque/check and the accounting will notify the Purchaser that the check is now release then that will be the time where the purchaser start buying and so on...

then put Ticket Number (Request No.) i dont the exact term but that will be the way for easy tracking of 1 or per transaction like each Request No. holds 1 canvas + ris, atp, rfc, dv, rr, lr per transaction

DONE — Request No. (ticket): format PR-YYYYMM-#### (e.g. PR-202610-0001), created with Canvas+RIS and copied to ATP → RFC → DV → RR → Liquidation. Form Nos (RIS→LR) use YYYYMMDD + sequence digits (e.g. RFC-20261019-00001).


Print blank anytime. Create in the system only when the prior document exists.

Form	Print empty	Create in system
RIS
Yes
Standalone OK (start of the chain)
ATP
Yes
Must link approved RIS
RFC
Yes
Draft optional; submit needs linked ATP
DV
Yes
Must come from the accounting package / prior docs — not a bare empty create
RR
Yes
Must link approved RFC after check/funds released
Flow: RIS → ATP → RFC → DV → check released → buy → RR → (Liquidation only if Cash Advance)

Rule of thumb: blank paper = print; real record = always tied to the previous step.

add here that can assign person in this department much likely for those person that still no department

in the maintenace or administrator system side for maintenance schedule module when schedule is completed this day or year or month make it cant be mark as complete again when its not yet 7 days before of another maintenance schedule... am i right?

whats the exact ordering and grouping for the links in the administrator sidebar


Room	Why the %
NewRoom1 · 100%
All equipment counted as Good
Room 204 · 50%
About half of its 6 items are Good (rest damaged/maint/etc.)
Admission / NewRoom2 · 33%
Roughly 1 of 3 Good
Registrar · 8%
0 equipment → formula still floors at 8% (never shows 0%)


Condition vs. Status Separation:Condition = 
How is the physical item? (Good, Defective, Damaged)   
Status = What is happening to the item in the system? (Active, Borrowed, Under Maintenance, For Replacement, Disposed)

in this part should we make the equipment in holding area in the room can be mark as deployed equipment in report form instead of unassigned since in the room that has holding area is we just make it for the ui... am i right?

on every procurement form in every input field put a limit/maximum input like from ris to lr

should we put in then inventory module, a (notification/lists) about that the new replacement item has been arrived to replace that item the that has need for replacement

in the school administrator make it has a dedicated that can view also the equipment details and person's details

in the school administrator update the signature history to include also about the atp, rfc,dv since currently its just about ris


dont put too much contents just important details and some that can direclty be action

the item split type aircon... isnt in when creating atp

When more than one person can hold the role, the card should show the assigned name, and the role only when nobody has been chosen yet. Switch to Agent mode if you want those labels changed.


make the form shows first when clicking any one of it here in the dashboard than going to another page direclty

in the receiving officer system dashboard simplify & update the contents also (remove unnecessary information/details) then avoid using not important to have subtitle

for the entire procurement process, for every tables from ris to lr put a dedicated export pdf feature

in the purchaser officer system dashboard retain/restay this part (image 1) and simplify & update the contents also (remove unnecessary information/details) then avoid using not important to have subtitle

if not cash advance like Direct Payment/Cash Installment make the Payee is 