
---------------------------------------------------------------------------------
- Developer README
---------------------------------------------------------------------------------

When you add or modify a database field or table in the ISPConfig database,
then follow these steps:

1) Add the field or table in the ispconfig3.sql file. This file contains the
   complete database dump which is used when ISPConfig gets installed.
   
2) Add your ALTER TABLE, or if it is a complete new table then the add table,
   statement(s) in MySQL syntax which is/are required to modify the current
   ispconfig database during update to the file upd_dev_collection.sql in the
   sql/incremental subfolder.
   
   Please do not create new patch sql files as those will be generated on
   new releases from the upd_dev_collection.sql file. Also please do not
   modify older (already released) patch files, they will not get executed
   again if the update was already run once on a system.
   
   After a patch has been executed, the dbversion field in the server table gets
   increeased to the version number of the last installed patch.
   
   If you like to run a patch file again for testing purposes on your dev machine,
   then set the number in "dbversion" field of the server table to be lower then
   the number of your patch.
   
Note: Incremental patches are supported for installed ISPConfig versions > 3.0.3.
      If the installed version is < 3.0.3, then the full update method is used.
	  In other words, ISPConfig 3.0.3 is the patch release (dbversion) 0 as the 
	  incremental update feature has been introduced in 3.0.3.




