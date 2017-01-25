# PayChecker Module for phpVMS
Allows the recalculation of Revenue & Virtual Pilot Pay within the phpVMS Eco-system

This module has been made specifically from a request, if you don't know what you are doing, then DO NOT download this module as it may screw up your database entries.
----------------------
1. Installation
----------------------

Make sure you have the phpVMS 5.5.x version of phpVMS (found here https://github.com/DavidJClark/phpvms_5.5.x)
Back Up mySQL Database (I won't be responsible for loss of data)
Place the files into their respective destination

This module should be complete, but may need some testing to perfect it.
----------------------
2. Usage
----------------------
This module has various endpoints to access the data.

	Pilot's Side
	-------
	In your url, go to http://yourvaurl.com/index.php/PayChecker and once you hit go, you are presented with information (pay and revenue) about the first PIREP in your database, to view others, append ?pirepid=$pirepid to the end of the url changing $pirepid with the pirepid of the PIREP you are trying to view.
	
	In your url, go to http://yourvaurl.com/index.php/PayChecker/estimatePay?pirepid=$pirepid and the pilots can get an estimate of the pay they will be receiving when the PIREP is accepted.
	
	Admin's Side
	-------
	Provided you have access, in your url, go to http://yourvaurl.com/admin/index.php/PayChecker for the possible options.
	The "Go" button basicaly does the same things as on the pilot's side
	The Recalculate Revenue button will allow you to recalculate the revenue for all pireps using the following formula
		(pilot's payrate / 60) * flighttime (in mins)
	The Recalculate Revenue & Pilot Pay button will allow you do the same as above, but it will also add in any missing ledgers (as the old ones should hold the correct amount already) and then recalculate all pilot's pay with all the "amount" values in the ledger table.
	
	As a request, you are able to view the pilot's PIREPS and the pay they have received from the relavent links in the admin panel.