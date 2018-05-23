Table of Contents
-----------------

* Introduction
* Requirements
* Installation
* Configuration
    * Filtering
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

This module creates a block called "Faculty Directory Listing". Place this block in an appropriate region to display a directory that contains all available faculty members. Additional blocks that filter the displayed faculty members can be easily created (see Filtering below). 

This module also creates a content type called "BYU Faculty Member". To add faculty members from BYU OIT's data, visit the configuration page (Home > Administration > Configuration > Content Authoring > BYU Faculty Directory Configuration). Select the appropriate checkboxes depending on if you want to download data, create content from downloaded data, or both, and click "Save Configuration".

Certain departments may want to manually edit faculty member fields (e.g. to add details about a specific course in the Courses Taught field), and this can be done by editing the faculty member's node and clicking the "[field name] Override" checkbox. This will tell the module to not override this field with data from OIT.

### Filtering

The faculty member directory uses the View module, which means the directory can be filtered by field, name, number of items, and so on. Make sure to pay attention to the display selection option on any dialog boxes when changing View options - for example, make sure "This Block (override)" is selected instead of "All Displays" to apply changes only to the current display.

#### Example - Choose Number of Items To Display on a Page
1. Navigate to the BYU Faculty Directory Listing View settings
    - Structure > Views > BYU Faculty Directory Listing > Edit
2. Choose the display to edit, if not selected
3. Under Pager, click on what is currently selected next to "Use Pager" (most likely "Display All Items")
4. Select "Paged output, full pager"
5. Then, you can edit settings such as number of items on a page and pager link labels
6. Click "Apply",  and then "Save"

#### Example - Display Only Adjunct Faculty
1. Navigate to the BYU Faculty Directory Listing View settings
    - Structure > Views > BYU Faculty Directory Listing > Edit
2. Duplicate the default display
    1. To the right of the display name, click Duplicate (display name)
    2. Change the display name of the new display to Adjunct Faculty by clicking on the current display name
    3. Also change the Title and Block Name options to match the new display name
3. Add a new filter under "Filter Criteria"
    1. Click on "Title/Rank (field_title)", and then Apply.
    2. Change "Is Equal To" to "Contains"
    3. Enter "Adjunct" in the Value field
    4. Click "Apply"
4. Click "Save"
5. A new block displaying only Adjunct Faculty members will now be available in Structure > Block Layout, and can be placed where desired


Work in Progress
----------------

Still do to:

	- Verify and finalize faculty member fields
	- Customize API key
	- Create submodule for individual departments, REST API
	- Filter data download by department
	- Fetch profile pictures, CVs from OIT
	- Automate download of data
	- Other tasks as needs arise
	

Maintainers
-----------

Current maintainers:
	- Sam Beckett (sbeck14) - https://www.drupal.org/user/1405076

