# Changelog

All notable changes to `payfast-onsite-subscriptions` will be documented in this file.

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
