# Changelog

All notable changes to `payfast-onsite-subscriptions` will be documented in this file.

## v1.6.0 - 2023-02-18

- Fix bug if you have a trial activated and you choose a yearly plan. The next due date was wrong
- Fix display bug that output 0000-00-00 on receipts

## v1.5.4 - 2023-02-17

- Remove a lot of commented code from tests
- Remove a placeholder for Unit test directory as there is an actual test now
- Remove 'jet' part of x-jet namespacing
- Add screenshot for menu

## v1.5.3 - 2023-02-15

- Bumped collission from version 6.3 to version 7.0
- Did composer update

## v1.5.2 - 2023-01-14

- Add UI conditionals for $user->hasExpiredGenericTrial and $user->onGenericTrial 

## v1.5.1 - 2023-01-11
- Removed index.php file in root which was related to an unrelated WHMCS module
- Added "ExpiredTrial" helpers as per updated Paddle code and added a the official test for it as well
- Tests were failing so added ENV variables in phpunit.xml.dist for merchant ID, key, and passphrase

## v1.5.0 - 2022-10-21

- Helper for status renamed from on_trial to on_generic_trial
- Helper now outputs days left of trial and the plan name
- ManagesSubscriptions trait can now return trial days left ->trialDaysLeft

## v1.4.1 - 2022-10-15

- Remove illuminate contracts as dependency
- Add normal logging back to webhook controller again
- If testmode variable isn't set it will not fail on merchant_id giving the user a bigger clue as to .env variables that haven't been added
- If the model doesn't have a first name or last name then just use name

## v1.4.0 - 2022-10-13

- Bump Nova minimum to version 4 by adding NovaRequest classes to actions
- Update readme to specify Nova 4
- Fix plural problem with the `Receipt[s]` resource name

## v1.3.0 - 2022-10-12

- Bump PHP to 8.1
- Bump L8 to L9

## v1.2.0 - 2022-10-12

- Bugfix: When a user is on trial, they must be able to cancel. Update Livewire component.
- Bugfix: When a user is still on trial, and they pay, then next billing date date must be added to end of trial date

## v1.1.3 - 2022-10-04

- Bugfix in receipts blade allow for null on PayFast field billing_date

## v1.1.2 - 2022-10-04

- Release

## v1.1.1 - 2022-10-02

- Add ability to create customer from Nova with trial days config variable
- Add billing component that looks like and needs Jetstream to work
- Add new DEBUG variable
- Add a banner that displays current subscription information
- Add new button that is just green
- Add more subscriber information in Nova, e.g. on Receipts
- Change description on item names to be consistent with subscription
- Swap some buttons around for subscription cancellations
- Simplify webhook ping test
- The receipts table will now update post subscription changes
- Remove lots of Log::debug() instead using Ray in the webhook controller
- Remove more references from Paddle Subscription object


## v1.1.0 - 2022-10-01

- Remove paid_at from receipts table
- Add billing_date as received from PayFast to the receipts table
- Add received_at to receipts table
- Create Payment::COMPLETE Enum to better handle incoming payments

## v1.0.3 - 2022-10-01

- Refine subscription cancellations on the front-end and back-end
- Removed ended_at as was not in use
- Create cancel2() routine to bypass Paddle
- Add test for cancel2()
- Remove cancelled_at date on front-end as it fails when there is a non-existing subscription

## v1.0.2 - 2022-09-25

- do a better implentation of test mode by expanding config variables and checking actual mode
- modify generate payment identifier test to use new test mode
- implement a callback test url for subscription callback testing

## v1.0.1 - 2022-06-06

- added some exceptions and much more testing

## v1.0.0 - 2022-06-06

- bump version due to composer minimum stability issues

## v0.1.1 - 2022-06-06

- migrated most of `laravel-payfast-onsite` functions to `payfast-onsite-subscriptions`. Ready for testing on live.

## v0.0.1 - 2022-06-02

- added many more tests - basically got all the tests from laravel-payfast-onsite across
- added test for dependency injection and ping to PayFast API
