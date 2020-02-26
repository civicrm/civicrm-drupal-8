CiviCRM Drupal 8 Module
=======================

This is a beta version of the integration module required to allow CiviCRM and 
Drupal 8 to work together.

For Views integration, please use: [civicrm_entity](https://github.com/eileenmcnaughton/civicrm_entity/tree/8.x-3.x). As per [dev/drupal#39](https://lab.civicrm.org/dev/drupal/issues/39), the views integration from this module has been removed.

Creating a new Drupal 8 site
----------------------------

To setup a new Drupal 8 installation with CiviCRM, there is a convenient
Composer template. This is the easiest way to get started for evaluating
CiviCRM on Drupal 8 or participating in development.

**See
[https://gitlab.com/roundearth/drupal-civicrm-project](https://gitlab.com/roundearth/drupal-civicrm-project)**

More notes are available on this page:  
https://lab.civicrm.org/dev/drupal/wikis/drupal8-composer

Adding to an existing site
--------------------------

Installing CiviCRM on Drupal 8 requires Composer, so your Drupal 8 site needs
to be fully 'Composerized', ie. starting from the
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
Composer template.

Assuming that is the case, there is a convenient Composer plugin than can
easily install CiviCRM to your Drupal 8 site.

**See
[https://gitlab.com/roundearth/civicrm-composer-plugin](https://gitlab.com/roundearth/civicrm-composer-plugin)**

Contribute
----------

If you want to contribute to the development of this module, please bear the following in mind:

* We currently do not have any automated testing setup on Drupal8, so please do a fair amount of testing.
* Many of the pull-request reviewers are not yet fully up to date on Drupal8. Please provide enough information so that we can understand the proposed change and the potential impacts.
* Please open issues on CiviCRM's Gitlab: https://lab.civicrm.org/dev/drupal/issues/

If you have any questions, please join the [Drupal channel on CiviCRM's chat](https://chat.civicrm.org/civicrm/channels/drupal)

We are very grateful for the contributions so far, a lot of amazing work has been done already. The CiviCRM Drupal8 integration is almost ready and there are many organisations already using it in production. However, we also need to invest into improving some of the CiviCRM core bits that will make it easier to use Composer, improve our continuous integration and test coverage for Drupal, and fix a few remaining difficult issues that for now require various workarounds.

Please consider contribution financially to making an official Drupal8 release happen:  
https://civicrm.org/make-it-happen/civicrm-drupal-8-the-official-release

Welcome to CiviCRM and thank you for contributing!

About CiviCRM
-------------

CiviCRM is web-based, open source, Constituent Relationship Management (CRM) software geared toward meeting the needs of non-profit and other civic-sector organizations.

As a non profit committed to the public good itself, CiviCRM understands that forging and growing strong relationships with constituents is about more than collecting and tracking constituent data - it is about sustaining relationships with supporters over time.

To this end, CiviCRM has created a robust web-based, open source, highly customizable, CRM to meet organizations’ highest expectations right out-of-the box. Each new release of this open source software reflects the very real needs of its users as enhancements are continually given back to the community.

With CiviCRM's robust feature set, organizations can further their mission through contact management, fundraising, event management, member management, mass e-mail marketing, peer-to-peer campaigns, case management, and much more.

CiviCRM is localized in over 20 languages including: Chinese (Taiwan, China), Dutch, English (Australia, Canada, U.S., UK), French (France, Canada), German, Italian, Japanese, Russian, and Swedish.

For more information, visit the CiviCRM website.
