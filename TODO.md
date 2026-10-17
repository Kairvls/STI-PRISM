# Implementation Plan

## Task 1: Quick Access Modal (Already Implemented)
- ✅ Quick Access cards changed to buttons with `openQuickAccessModal()` 
- ✅ Quick Access Modal HTML added
- ✅ JavaScript functions for modal
- ✅ Routes added in web.php
- ✅ AdminController methods added

## Task 2: RIS Print - "Issued by:" Section Updates
- ✅ Update `PurchaserController::printRis()` to fetch RIS items, President name from approval logs
- ✅ Update `resources/views/purchaser/ris/print.blade.php` to properly display the "Issued by:" section for:
  - ✅ Co-signed (Admin co-signed after President) → Shows `ris_issued_by_signature` + `ris_issued_by_date`
  - ✅ Forwarded to President (President signed with base64) → Shows President name + signature image + `ris_approved_by_date`
  - ✅ Direct Approved (Admin directly approved) → Shows admin name + `ris_approved_by_date`


make the easy flow to understand about this procurement process in our system in sti college ormoc

the sentence i note about the process:

The start of procurement is the Purchaser first make the Canvas + RIS form then submit to School Administrator, then the School Administrator will then approve it directly or pass it to President for approval then if it pass to President, after the President approved it the president will pass/return it back to School Administrator then the School administrator will then pass/return it back to Purchaser, then the Purchaser will submit the Canavs +  RIS to School Supply/Material Officer then the School Supply/Material Officer will make an ATP base on the RIS + Canvas then the School Supply/Material Officer will submit it to School Administrator for approval and after the School Administrator approve the ATP  it will be pass back again to School Supply/Material Officer then the School Supply/Material Officer will then pass the approved ATP back to Purchaser then the Purchaser make Request for Check and submit it to School Administrator with the attachment of approved Canvas + RIS, approved ATP then after the School Administrator approved it it will be pass back again to the Purchaser then the Purchaser will submit the approved Canvas + RIS + ATP + RFC to Accounting then the Accounting will make a Disbursement Voucher form with the attachment of approved Canvas + RIS, ATP, RFC to School Administrator then after the School Administrator approved it will be pass back to Accounting then the accounting will submit the approved Disbursement Voucher by the School Administrator then with already the approved Canvas + RIS, ATP, RFC to President then the President after approval it will be pass back to Accounting the approved Disbursement Voucher with Canvas + RIS, ATP, RFC and thats the time where the Accounting notify the Purchaser that the Cheque/Check been release then the Purchaser will collect the cheque/check manually to the offie then the Purchaser proceed to buying base on the request for check (or release check) the option is about Direct Payment, Cash Installment, Cash Advances then if Cash Advances that will connect to the RR then the RR with the OR (Order Receipt) no matter whats the funds incase of theres a changes like in the cheque/check the funds is 20k but because of sale/discount in the OR its now 15k then the 5k change will be return manually to cashier but the 5k change will be record in the RR record and that RR will be submitted to the Receiving Officer for the second verification of everything like the equipments quality, condition, &  quantity, base on the OR, then if only cash advances the Purchaser will proceed to Liquidation Report where the 5k change is being dectate/put 

(Cash Advances -> Liquidation Report if not like Direct Payment or Cash Installment its only end in RR) 

PROCUREMENT PROCESS 

Procurement Process Flow Overview
Phase 1: Canvas & RIS  ➜  Phase 2: ATP  ➜  Phase 3: RFC  ➜  Phase 4: DV & Check Release  ➜  Phase 5: Purchasing  ➜  Phase 6: RR & Liquidation

Phase 1: Request & Approval (Canvas + RIS)
	Purchaser creates the Canvas + Requisition and Issue Slip (RIS) form.
	Purchaser submits the form to the School Administrator.
	School Administrator evaluates the form:
	Direct Approval: Approves it directly, or
	President Approval Route: Passes it to the President for approval.
	If sent to the President: President approves → returns to School Administrator.
	School Administrator returns the approved Canvas + RIS back to the Purchaser.
Phase 2: Authority to Purchase (ATP)
	Purchaser submits the approved Canvas + RIS to the School Supply / Material Officer.
	School Supply / Material Officer makes the Authority to Purchase (ATP) form based on the Canvas + RIS.
	School Supply / Material Officer submits the ATP to the School Administrator for approval.
	School Administrator approves the ATP → returns it to the School Supply / Material Officer.
	School Supply / Material Officer passes the approved ATP back to the Purchaser.
Phase 3: Request for Check (RFC)
	Purchaser creates a Request for Check (RFC) and attaches:
	Approved Canvas + RIS
	Approved ATP
	Purchaser submits the package (Canvas + RIS + ATP + RFC) to the School Administrator.
	School Administrator approves the RFC → returns the approved package back to the Purchaser.
Phase 4: Disbursement Voucher (DV) & Check Release
	Purchaser submits the approved package (Canvas + RIS + ATP + RFC) to Accounting.
	Accounting creates a Disbursement Voucher (DV) with all attached documents and submits it to the School Administrator.
	School Administrator approves the DV → returns it to Accounting.
	Accounting submits the approved DV (along with Canvas + RIS + ATP + RFC) to the President.
	President signs/approves the DV → returns the complete approved package back to Accounting.
	Accounting prepares the check and notifies the Purchaser.
	Purchaser manually gets the check from the office.
Phase 5: Purchasing & Payment Options
The Purchaser proceeds to purchase the requested items using one of three payment methods:
Payment Method	End of Workflow
Direct Payment	Ends at Receiving Report (RR)
Cash Installment	Ends at Receiving Report (RR)
Cash Advance	Requires Receiving Report (RR) + Liquidation Report
Phase 6: Receiving & Liquidation (RR & Changes/Refunds)
1. Receiving & Verification
	The Purchaser buys the items and gets an Official Receipt (OR) from the supplier.
	A Receiving Report (RR) is created alongside the OR.
	The RR + OR package is submitted to the Receiving Officer for second-level physical verification (checking item condition, quality, and quantity against the OR).
2. Handling Remaining Change (e.g., ₱20,000 Check vs. ₱15,000 OR)
	If the check was ₱20,000 but the actual purchase cost ₱15,000 due to discounts/sales:
	The ₱5,000 change is manually returned to the Cashier.
	The ₱5,000 change is logged in the RR record.
3. Liquidation Route (Cash Advance only)
	Direct Payment / Cash Installment: Flow completes after the RR and verification.
	Cash Advance: The Purchaser must complete a Liquidation Report submit to Accounting detailing the total check amount, actual spending (based on OR), and the returned ₱5,000 change.

Quick Summary Table
Step	Form / Document	Primary Handler	Next Recipient / Approver
1	Canvas + RIS	Purchaser	School Administrator (→ President if required)
2	ATP	School Supply Officer	School Administrator
3	RFC	Purchaser	School Administrator
4	Disbursement Voucher (DV)	Accounting	School Administrator → President
5	Check Pickup & Buying	Purchaser	Supplier (Direct / Installment / Cash Advance)
6	Receiving Report (RR)	Purchaser & Receiving Officer	Verified against OR & items
7	Liquidation Report	Purchaser	Accounting / Cashier (Cash Advance only)

