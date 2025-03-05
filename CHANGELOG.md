# Changelog

All notable changes to `payfast-onsite-subscriptions` will be documented in this file.

## v2.7 - 2025-03-05

- When sending plans to Payfast, don't send the plan ID anymore but rather the plan string e.g. 0|monthly
- Fix subscriptions blade to (int) for days remaining and fix displaying of plan names
- Cast plan_id to string instead of integer

## v2.5 - 2025-02-28

- Implement Spark style plan configuration

## v2.4 - 2025-02-27

- Two additional Livewire 3 upgrade fixes and fix a redirect response return type

## v2.3 - 2025-02-27

- Fix camel case name spacing issue in the Subscriptions component
- Do not down on migrations anymore and convert to anonymous classes
- New Livewire 3 compatible Payfast modal event opener

## v2.2 - 2024-11-26

- Update Orchestra Testbench to version 9 for Laravel 11 compatibility
- Convert test suite from PHPUnit to Pest
- The minimum PHP version is now PHP 8.3
- The minimum Laravel version is now Laravel 11
- Update GitHub workflows
- Made migrations anonymous and added PHP types

## v2 BETA

- Updated Orchestra Testbench to version 8 for Laravel 10 compatibility
- Installed Livewire Beta 3
- Marked all other Composer packages "*"
- Some cleaning up of banner.blade.php

## v1.6.0 - 2023-02-18

- Fix the bug if you have a trial activated, and you choose a yearly plan. The next due date was wrong
- Fix display bug that output 0000-00-00 on receipts

## v1.5.4 - 2023-02-17

- Remove a lot of commented code from tests
- Remove a placeholder for Unit test directory as there is an actual test now
- Remove 'jet' part of x-jet namespacing
- Add screenshot for the menu

## v1.5.3 - 2023-02-15

- Bumped collision from version 6.3 to version 7.0
- Did composer update

## v1.5.2 - 2023-01-14

- Add UI conditionals for $user->hasExpiredGenericTrial and $user->onGenericTrial 

## v1.5.1 - 2023-01-11
- Removed index.php file in root which was related to an unrelated WHMCS module
- Added "ExpiredTrial" helpers as per updated Paddle code and added an official test for it as well
- Tests were failing so added ENV variables in phpunit.xml.dist for merchant ID, key, and passphrase

## v1.5.0 - 2022-10-21

- Helper for status renamed from on_trial to on_generic_trial
- Helper now outputs days left of trial and the plan name
- ManagesSubscriptions trait can now return trial days left ->trialDaysLeft

## v1.4.1 - 2022-10-15

- Remove illuminate contracts as dependency
- Add normal logging back to webhook controller again
- If test mode variable isn't set, it will not fail on merchant_id giving the user a bigger clue as to .env variables that haven't been added
- If the model doesn't have a first name or last name, then just use name

## v1.4.0 - 2022-10-13

- Bump the Nova minimum to version 4 by adding NovaRequest classes to actions
- Update readme to specify Nova 4
- Fix a plural problem with the `Receipt[s]` resource name

## v1.3.0 - 2022-10-12

- Bump PHP to 8.1
- Bump L8 to L9

## v1.2.0 - 2022-10-12

- Bugfix: When a user is on trial, they must be able to cancel. Update the Livewire component.
- Bugfix: When a user is still on trial, and they pay, then the next billing date must be added to the end of trial date

## v1.1.3 - 2022-10-04

- Bugfix in a receipt blade allows for null on Payfast field billing_date

## v1.1.2 - 2022-10-04

- Release

## v1.1.1 - 2022-10-02

- Add the ability to create customer from Nova with trial days config variable
- Add a billing component that looks like and needs Jetstream to work
- Add new DEBUG variable
- Add a banner that displays current subscription information
- Add a new button that is just green
- Add more subscriber information in Nova, e.g. on Receipts
- Change description on item names to be consistent with subscription
- Swap some buttons around for subscription cancellations
- Simplify webhook ping test
- The receipt table will now update post-subscription changes
- Remove lots of Log::debug() instead using Ray in the webhook controller
- Remove more references from a Paddle Subscription object


## v1.1.0 - 2022-10-01

- Remove paid_at from receipt table
- Add billing_date as received from Payfast to the receipt table
- Add received_at to receipts table
- Create Payment::COMPLETE Enum to better handle incoming payments

## v1.0.3 - 2022-10-01

- Refine subscription cancellations on the front-end and back-end
- Removed ended_at as was not in use
- Create cancel2() routine to bypass Paddle
- Add test for cancel2()
- Remove cancelled_at date on front-end as it fails when there is a non-existing subscription

## v1.0.2 - 2022-09-25

- do a better implementation of test mode by expanding config variables and checking actual mode
- modify generate payment identifier test to use new test mode
- implement a callback test url for subscription callback testing

## v1.0.1 - 2022-06-06

- added some exceptions and much more testing

## v1.0.0 - 2022-06-06

- bump version due to composer minimum stability issues

## v0.1.1 - 2022-06-06

- migrated most of `laravel-payfast-onsite` functions to `payfast-onsite-subscriptions`. Ready for testing on live.

## v0.0.1 - 2022-06-02

- added many more tests—basically got all the tests from laravel-payfast-onsite across
- added test for dependency injection and ping to Payfast API
