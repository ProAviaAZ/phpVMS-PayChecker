<?php
class PayChecker extends CodonModule {
	/***
	**
	**
	** HTMLHead (For Sidebar)
	**
	***/
	public function HTMLHead() {
		$this->set('sidebar', 'PayChecker/sidebar.php');
	}

	/***
	**
	**
	** NavBar (for Addons List)
	**
	***/
	public function NavBar() {
		echo '<li><a href="'.SITE_URL.'/admin/index.php/PayChecker">Pay & Revenue Checker</a></li>';
	}

	/***
	**
	**
	** Index
	** @echo
	**
	***/
	public function index() {
		echo '<a href="'.SITE_URL.'/index.php/PayChecker/go?pirepid=1">Go! - Check PIREP Pay (change pirepid in url)</a>';
		if(PilotGroups::group_has_perm(Auth::$usergroups, FULL_ADMIN)) {
			echo '<br /><br />';
			echo '<a href="'.SITE_URL.'/admin/index.php/PayChecker/resetRevenue">Reset Revenue</a>';
			echo '<br /><br />';
			echo '<a href="'.SITE_URL.'/admin/index.php/PayChecker/resetRevenueAndPay">Reset Pay + Revenue</a>';
		}
	}

	/***
	**
	**
	** Resets revenue for all pireps
	** @returns updated data
	**
	***/
	public function resetRevenue() {
		if(!PilotGroups::group_has_perm(Auth::$usergroups, FULL_ADMIN)) {
			header("Location: ".adminurl('/PayChecker'));
			return false;
		}
		
		echo '<h3>Resetting Revenue</h3>';
		# Get the pireps
		$sql = "SELECT * FROM ".TABLE_PREFIX."pireps";
		$res = DB::get_results($sql);

		// Define Variables
		$payment_type = PILOT_PAY_HOURLY;
		$count = 0;

		foreach($res as $pirep) {
			$gross = $pirep->price * $pirep->load;

			if($payment_type == PILOT_PAY_HOURLY) {
				$pilot = PilotData::getPilotData($pirep->pilotid); # Get the pilot info for payrate in case pay field is changed later
				$flighttime = explode('.', $pirep->flighttime); # split the flighttime into two parts
				$flighttime_min = ($flighttime[0] * 60) + ($flighttime[1]); # Calculate no. minutes
				$pilotpay = ($pilot->payrate / 60) * $flighttime_min; # Finalise the payment

				// This was the original
				// Only multiplies payrate (pay field) by the flight time without taking into account the minutes / 60 but min. / 100
				# $pilotpay = $data['pilotpay'] * $data['flighttime'];
			} else {
				$pilotpay = $pirep->pilotpay;
			}

			if ($pirep->expenses == '') $pirep->expenses = 0;

			$revenue = $gross - $pirep->expenses - $pirep->fuelprice - $pilotpay;
			PIREPData::editPIREPFields($pirep->pirepid, array('revenue' => $revenue));
			$count++;
		}

		echo '<br /> <br />';
		echo 'Revenue Updated for '.$count;
	}

	/***
	**
	**
	** Resets Revenue for all pireps and updates pilots with their correct pay without deleting any data.
	** Should just update it
	** @returns updated data
	**
	***/
	public function resetRevenueAndPay() {
		if(!PilotGroups::group_has_perm(Auth::$usergroups, FULL_ADMIN)) {
			header("Location: ".adminurl('/PayChecker'));
			return false;
		}

		echo '<h3>Resetting Revenue & Pilot Pay</h3>';
        // PilotData::resetMissingLedger
		# Get the pireps
		$sql = "SELECT * FROM ".TABLE_PREFIX."pireps";
        $res = DB::get_results($sql);
		
		// Define Variables
        $payment_type = PILOT_PAY_HOURLY;
		$count = 0;
		$pilot_count = 0;
		$ledger_count = 0;
		
    foreach($res as $pirep) {
			$gross = $pirep->price * $pirep->load;

			if($payment_type == PILOT_PAY_HOURLY) {
				$pilot = PilotData::getPilotData($pirep->pilotid); # Get the pilot info for payrate in case pay field is changed later
				$flighttime = explode('.', $pirep->flighttime); # split the flighttime into two parts
				$flighttime_min = ($flighttime[0] * 60) + ($flighttime[1]); # Calculate no. minutes
				$pilotpay = ($pilot->payrate / 60) * $flighttime_min; # Finalise the payment

				// This was the original
				// Only multiplies payrate (pay field) by the flight time without taking into account the minutes / 60 but min. / 100
				# $pilotpay = $data['pilotpay'] * $data['flighttime'];
			} else {
				$pilotpay = $pirep->pilotpay;
			}

			if ($pirep->expenses == '') $pirep->expenses = 0;

			$revenue = $gross - $pirep->expenses - $pirep->fuelprice - $pilotpay;
			PIREPData::editPIREPFields($pirep->pirepid, array('revenue' => $revenue));
			$count++;
    }
		
		echo '<h4>Updating Ledger</h4>';
		/****** PilotPay Adjustment ******/
		// Fill missing ledger first
        $sql = 'SELECT * FROM `'.TABLE_PREFIX.'pireps`
                    WHERE `accepted`='.PIREP_ACCEPTED;
        
        $res = DB::get_results($sql);
		
		echo count($res).' Accepted PIREPS Found!';
		echo '<br /> <br />';
		
		foreach($res as $pirep) {
			$exists = LedgerData::getPaymentByPIREP($pirep->pirepid);
			if(!$exists) {
				$pilot = PilotData::getPilotData($pirep->pilotid);
				// Calculate the pay using the new method
				$flighttime = explode('.', $pirep->flighttime);
				$flighttime_min = ($flighttime[0] * 60) + ($flighttime[1]);
				$totalpay = ($pilot->payrate / 60) * $flighttime_min;
				
				$params = array(
					'pirepid' => $pirep->pirepid,
					'pilotid' => $pirep->pilotid,
					'paysource' => PAYSOURCE_PIREP,
					'paytype' => $pirep->paytype,
					'amount' => $totalpay,
				);

				LedgerData::addPayment($params);
				echo 'Added Ledger for PIREP #'.$pirep->pirepid.' & Pilot - '.$pilot->firstname.' '.$pilot->lastname;
				echo '<br />';
				$ledger_count++;
			} else {
				echo 'SKIPPING: Ledger already exists for PIREP #'.$pirep->pirepid;
				echo '<br />';
			}
		}
		
		// Reset It
        $allpilots = PilotData::GetAllPilots();
		
		echo '<h4>Updating Pilot Pay</h4>';
        foreach ($allpilots as $p) {
            $total = PilotData::resetPilotPay($p->pilotid);
            echo "{$p->firstname} {$p->lastname} - total $ {$total}<br />";
			$pilot_count++;
        }

		echo '<br /> <br />';
		echo 'Revenue Updated for '.$count.' - Ledger Updated for '.$ledger_count.' - Pilot Pay Adjusted for '.$pilot_count.' Pilots';
	}
	
	/***
	**
	**
	** Displays data in format
	**
	***/
	public function displayData($name, $data) {
		echo $name;
		echo '<br />';
		echo '<pre>';
		print_r($data);
		echo '</pre>';

		echo '<br />';
		echo '<br />';
	}
	
	/***
	**
	**
	** Table to view last pirep info (and pay) for all pilots
	** @returns view
	**
	***/
	public function viewPIREPS() {
		$allpilots = PilotData::getAllPilots();
		
		$this->set('allpilots', $allpilots);
		$this->show('PayChecker/view_pireps');
	}
	
	/***
	**
	**
	** Table to view all pireps for a pilot
	** @returns view
	**
	***/
	public function viewPilotPIREPS($pilotid = '') {
		$pilotid = intval($pilotid);
		$pilot = PilotData::getPilotData($pilotid);
		$allpireps = PIREPData::getAllReportsForPilot($pilotid);

		$this->set('pilot', $pilot);
		$this->set('allpireps', $allpireps);
		$this->show('PayChecker/view_pilot_pireps');
	}
	
	/***
	**
	**
	** Use this function to return the pilot's current pay in a view
	** Just pass in a ?pirepid=$pirep->pirepid value and it will return the pay correctly.
	** e.g. http://yourvaurl.com/index.php/PayChecker/getEstimatePay?pirepid=1
	**
	***/
	public function getEstimatePay($pirepid = '') {
		if(!$pirepid) {
			$pirepid = DB::escape($this->get->pirepid);
		}
		$sql = 'SELECT * FROM `'.TABLE_PREFIX.'pireps` WHERE `pirepid` = '.$pirepid;
		$pirep = DB::get_row($sql);
		
		$pilot = PilotData::getPilotData($pirep->pilotid);
		
		// Explode the flighttimes
		$flighttime = explode('.', $pirep->flighttime);
		$flighttime_min = ($flighttime[0] * 60) + ($flighttime[1]);
		$totalpay = ($pilot->payrate / 60) * $flighttime_min;
		
		// $totalpay = $pilot->payrate * $pirep->flighttime; # What you were getting before
		return FinanceData::formatMoney($totalpay);
	}
}