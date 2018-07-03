# Developer Notes

* It appears that if you try to install the module and there are existing fields that share the same name as fields inside the module, the installation fails/throws a fit. Not sure how to resolve this issue - maybe make unique fields like field_byu_f_d_profile_image and field_byu_f_d__name? There's got to be some way to overwrite it or something. Or share the existing fields.
  * The module has been re-written to have all types of content use field_byu_f_d_*. This is best practice, and shouls stop the module from conflicting with previously installed modules.

* To reinstall, you have to uninstall, delete the content type, and then reinstall. I'm not sure if there's some sort of script that runs when you uninstall that would automatically remove the content type/fields/etc. but this might be something to look into.
  * This has been updated. It requires further testing, but the module should uninstall itself, all content types, and the view.

* You might want to double check the formatting of the fields:
  * This is found in BYUFacultyDirectoryForm::createSingleFacultyMember()
  * I've tried to parse through the XML so as to make things look pretty when it's displayed on the profile/listing page, but some tweaks probably need to be made

* I added a link to the CV and education info to the node--byu_faculty_member.html.twig template, but you'll need to update the component/CDN to add the cv and education slots.

* Ask Shawn Ward for the CAEDM/Engineering College OIT API key as well as information on connecting to the decoysam-ct.et.byu.edu proxy server.

* To do list:
  * Verify and finalize faculty member fields
  * Automation of Data Retrieval (from OIT and parent module)
  * Sanitize parent base URL input (add <http://> if needed, trailing slash, etc.)

* Feel free to ask any questions and I'll get back to you as soon as I can - sbeck14@me.com
