Table of Contents
-----------------

* Introduction
* Requirements
* Installation
* Configuration
* Work in Progress
* Maintainers


Introduction
------------

The BYU Faculty Directory module adds a content type and view for displaying a directory and profiles of BYU faculty members.


Requirements
------------

This module requires the following modules:

* Views (https://drupal.org/project/views)


Installation
------------

Install this module as you normally would a standard Drupal 8 module See:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further information.


Configuration
-------------

This module creates a block called "Faculty Directory Listing". Place this block in an appropriate region to display the faculty directory.

This module also creates a content type called "BYU Faculty Member". To add faculty members from BYU OIT's data, visit the configuration page (Home > Administration > Configuration > Content Authoring > BYU Faculty Directory Configuration). Select the appropriate checkboxes depending on if you want to download data, create content from downloaded data, or both, and click "Save Configuration".

Certain departments may want to manually edit faculty member fields (e.g. to add details about a specific course in the Courses Taught field), and this can be done by editing the faculty member's node and clicking the "[field name] Override" checkbox. This will tell the module to not override this field with data from OIT.


Work in Progress
----------------

Still do to:
	- Verify and finalize faculty member fields
	- Customize API key
	- Customize background image on profile page
	- Create submodule for individual departments, REST API
	- Filter data download by department
	- Pagination of faculty directory Listing
	- Fetch profile pictures, CVs from OIT
	- Automate download of data
	- Other tasks as needs arise
	

Maintainers
-----------

Current maintainers:
	- Sam Beckett (sbeck14) - https://www.drupal.org/user/1405076

