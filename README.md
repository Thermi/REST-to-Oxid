REST-to-Oxid
============

A php script to import files from a REST interface into the Oxid Shopsystem

How to use:
==========

Put the database host, user, password and database in the corresponding variables
in the script, then move or copy the script to the root folder of the oxid installation.

Then run the script by accessing it over http on the server.
E.g.: "http://example.com/import.php"

If the script ran successfully,
the first line of the web page should say "Import successfully completed!".
Below it, the Oxid webshop should be visible.

If it failed, the error will be shown on the screen
and the database will not be changed.

After you used it, it is advised to remove it from the web server.