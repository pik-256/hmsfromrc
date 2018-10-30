# hmsfromrc

This repository is a fork of "hMailServer from RoundCube" which can be found from https://hmsfromrc.codeplex.com/

### Changes from original project

* Plugin UI moved to Preferences > Server Settings to reduce clutter (previously Preferences > hmsfromrc)
* English and Turkish translation added
* Autoresponder UI refinements
* Forwarding settings added

Please note, this only works reliably with DCOM. hMailServer internally uses a cache which makes direct database manipulation is unreliable. I did not tested the original author's SQL implementation. Use with care. Forwarding will not work using SQL (because I did not implement it)
I'm considering to drop the SQL support.

### Additional changes

* FIX for compatibility with RC 1.3
* Polish translation

---

## Original README

Required plugins:
    - jqueryui

See config.inc.php.dist.

This plugin directly accees the hMailServerDatabase bypassing the DCOM server.

Tested on :
    - windows 2012 standard
	- php 5.5.5
	    - php driver from http://social.technet.microsoft.com/Forums/sqlserver/fr-FR/e1d37219-88a3-46b2-a421-73bfa33fe433/unofficial-php-55-drivers-x86
	- sql server 2012 express
	- hMailServer 5.4B1950
	- RoundCube 0.9.5

----- ----- ----- ----- ----- -----
Remarks:

for at least hMailServer 5.4:

You should have to update PHPWebAdmin by editing include/functions.php as follows:
 	
  function PreprocessOutput($outputString)
  {
      //return htmlspecialchars($outputString);
      return htmlspecialchars($outputString, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
  }

----- ----- ----- ----- ----- -----
Why another plugin for hMailServer ?
- for training
- because the existing one are, imho, too obfuscated.

Why not DCOM ?
- I really dislike COM/DCOM
- I imagine RoundCube being hosted far from hMailServer

But ther is now a COM/DCOM version of the plugin.
Please read : https://www.hmailserver.com/documentation/latest/?page=howto_dcom_permissions
