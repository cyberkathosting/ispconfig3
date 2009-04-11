since i don't have the time to develope this module at the moment i want to explain how the database is constructed, so that someone else is able to contiue on this module.

First of all: Why do we need this module?
-----------------------------------------
At the moment it is possible for a customer to register every domain, even subdomains belonging to other customers!
With the "domain"-module it is possible to assign domains to customers and to implement domain-robot-tools.

The next step would be to implement selector boxes to other modules like "dns", "mail", etc. where the customer can only select domains which belong to him.

THE TABLES:
-----------

domain
______

- domain_provider_id: reference to table domain_provider; over which provider was the domain ordered!
- provider_domain_id: reference-id from the domain-provider
- ...
- added_at: record creation date
- connected_at: date at which the domain was connected - important for billing!
- disconnected_at: empty by default. Date when the domain was canceled.
- status: status-info from the registrar


domain_handle
-------------
most registrars work with handles. In this table we assign handles from different registrars (DENIC, etc.) to the ispc-clients


domain_provider
---------------
the domainprovider is the one where the domains are ordere at (Hetzner, 1und1, HostEurope, etc.)

- provider: name of the Provider
- is_extern: BOOL; Only True if the client has ordered the domain somewhere else on his own and want's to use the domain on the ISPC-Server. - IMPORTANT FOR DOMAIN-BILLING!
- domainrobot_interface: for future development - describes the Providers domainrobot-interface: could be NULL, EMAIL, SOAP, XML, etc.

domain_tld
----------
all available TopLevelDomains

- tld: The TopLevelDomain (without dot: e.g.: "de" NOT ".de")
- domain_provider: reference to table domain_provider; which provider is responsible for registration
- domain_registrar: who is the domain registrar (DENIC, EURID, etc.); same name as in domain_handle - IMPORTANT for Table domain_handle: e.g.: When the domain test.de is ordered only DENIC-Handles from the Customer are displayed and valid!



cheers

if you have any question you can contact me over the forum.
http://www.howtoforge.com/forums/member.php?u=50859

2009-04-11