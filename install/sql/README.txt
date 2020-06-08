
---------------------------------------------------------------------------------
- Developer README
---------------------------------------------------------------------------------

When you add or modify a database field or table in the ISPConfig database,
then follow these steps:

1) Add the field or table in the ispconfig3.sql file. This file contains the
   complete database dump which is used when ISPConfig gets installed.
   
2) Edit the file "incremental/upd_dev_collection.sql" which contains the SQL
   statements (alter table, add table, update, etc.) in MySQL syntax which
   are required to modify the current ispconfig database during update.

   The upd_dev_collection.sql file contains all db schema modifications
   for changes made since the last ISPConfig release.  If SQL statements
   are already present in the file when you make your additions, add yours
   to the end of the file, and do not remove any existing statements.

   When a new ISPConfig update is released, the contents of
   upd_dev_collections.sql will move to an sql patch file, using the naming
   scheme upd_0001.sql, upd_0002.sql, upd_0003.sql etc.
   
   A patch file may contain one or more SQL modification statements. Every patch
   file gets executed once in the database, so do not modify older (already released)
   patch files, they will not get executed again if the update was already run 
   once on a system, and will result in missing updates on any system where they
   have not run yet.
   
   After a patch has been executed, the dbversion field in the server table gets
   increeased to the version number of the last installed patch.
   
   If you like to run a patch file again for testing purposes on your dev machine,
   then set the number in "dbversion" field of the server table to be lower then
   the number of your patch.
   
Note: Incremental patches are supported for installed ISPConfig versions > 3.0.3.
      If the installed version is < 3.0.3, then the full update method is used.
	  In other words, ISPConfig 3.0.3 is the patch release (dbversion) 0 as the 
	  incremental update feature has been introduced in 3.0.3.




