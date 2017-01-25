<?php
class PayChecker extends CodonModule {
	/* Module to check PIREP & Pilot Pay Calculation */
	/* For Debugging Purposes Only! Scripts have been derived from dataclasses throughout phpVMS and have been modified to allow no data to be submitted to the DB affecting pilot's total pay, etc. */

	protected $pirepid = '1'; // File a PIREP then change this number to the PIREPID of that PIREP

	public function index() {
		echo '<a href="'.SITE_URL.'/index.php/PayChecker/Go">Go! - Check PIREP Current Pay & Revenue</a>';
	}

	/***
	**
	**
	** Public Function Go
	** Able to pass a ?pirepid=$pirepid parameter to view info for each pirep, otherwise defaults to protected value above
	** Returns pirep pay and revenue based on different algorithms
	**
	***/
	public function Go() {
	if($this->get->pirepid) {
		$this->pirepid = DB::escape($this->get->pirepid);
	}
		// PIREPData.class.php => Calculate PIREPPayment
    $pirep = DB::get_row(
        'SELECT `pirepid`, `pilotid`,
                `flighttime_stamp`, `pilotpay`,
            `paytype`, `flighttype`, `accepted`
        FROM `'.TABLE_PREFIX.'pireps`
        WHERE `pirepid`='.$this->pirepid
    );

    if($pirep->paytype == PILOT_PAY_HOURLY) {
        # Price out per-hour?
        $peices = explode(':', $pirep->flighttime_stamp);
        $minutes = ($peices[0] * 60) + $peices[1];
        $amount = $minutes * ($pirep->pilotpay / 60);

    } elseif($pirep->paytype == PILOT_PAY_SCHEDULE) {
        $amount = $pirep->pilotpay;
    }
		
	$pilot = PilotData::getPilotData($pirep->pilotid);
	echo '<h3>PIREP Data for PIREP #'.$this->pirepid.' & Pilot - '.$pilot->firstname.' '.$pilot->lastname.'</h3>';
	echo '<br />';

    self::displayData("PIREPData => CalculatePIREPPayment", $amount);

		// PIREPData.class.php => PopulatePIREPFinance
		// Nothing in this function as it sets the DB's pilotpay field to the pilot's payrate

		// PIREPData.class.php => getPIREPRevenue
		// Nothing in this function as it returns revenue data only

		// PilotData.class.php => resetPilotPay
		$sql = "SELECT SUM(`amount`) AS `total` FROM `".TABLE_PREFIX."ledger` WHERE `pilotid` = '$pirep->pilotid'";
		$total = DB::get_row($sql);

    self::displayData("PilotData => resetPilotPay", $total->total);

		$this->revised();
	}

	/***
	**
	**
	** Revised algorithm which may or may not work, just a test
	** All it does it convert the values to show that it doesn't matter whether . or : is used
	** It should produce the same end value
	**
	***/
	public function revised() {
		// Set the PIREP & Define the original flighttime variables
		$sql = "SELECT * FROM ".TABLE_PREFIX."pireps WHERE pirepid = '$this->pirepid'";
		$pirep = DB::get_row($sql);
		$flighttime = $pirep->flighttime;
		$flighttime_stamp = $pirep->flighttime_stamp;

		// Define Pilot's Payrate
		$pilot = PilotData::getPilotData($pirep->pilotid);
		$payrate = $pilot->payrate;
		// $payrate = 60; // For my debugging purposes
		
		// Display The Payrate
		self::displayData("Current Pilot's Payrate (per hour)", $payrate);

		// Explode the flighttimes
		$flighttime = explode('.', $flighttime);
		$flighttime_stamp = explode(':', $flighttime_stamp);

		// Grab the Flighttime Decimal field values
		$flighttime_hour = $flighttime[0];
		$flighttime_min = ($flighttime_hour * 60) + ($flighttime[1]);

		// Grab the Flighttime_Stamp field values
		$flighttime_stamp_hour = $flighttime_stamp[0];
		$flighttime_stamp_min = ($flighttime_stamp_hour * 60) + ($flighttime[1]);

		$totalpay_decimal = ($payrate / 60) * $flighttime_min;
		$totalpay_stamp = ($payrate / 60) * $flighttime_stamp_min;

		$totalpay_original = $payrate * $pirep->flighttime;


		self::displayData("New Algorithm Using Decimal Form", $totalpay_decimal);
		self::displayData("New Algorithm Using Flight Stamp Form", $totalpay_stamp);

		self::displayData("What you are getting now (I think)", $totalpay_original);
		
		// Revenue
        if(!empty($pirep->payforflight)) {
            $pilot->payrate = $pirep->payforflight;
            $payment_type = PILOT_PAY_SCHEDULE;
        } else {
            $payment_type = PILOT_PAY_HOURLY;
        }
		
		$revenue = PIREPData::getPIREPRevenue(json_decode(json_encode($pirep), true), $payment_type);
		
		self::displayData("Revenue For This PIREP", $revenue);
	}

	/***
	**
	**
	** Use this function to return the pilot's current pay
	** Just pass in a ?pirepid=$pirep->pirepid value and it will return the pay correctly.
	** e.g. http://yourvaurl.com/index.php/PayChecker/estimatePay?pirepid=1
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
	** Use this function to echo the pilot's current pay onto a page
	** Just pass in a ?pirepid=$pirep->pirepid value and it will return the pay correctly.
	** e.g. http://yourvaurl.com/index.php/PayChecker/estimatePay?pirepid=1
	**
	***/
	public function estimatePay() {
		$pirepid = DB::escape($this->get->pirepid);
		$sql = 'SELECT * FROM `'.TABLE_PREFIX.'pireps` WHERE `pirepid` = '.$pirepid;
		$pirep = DB::get_row($sql);
		
		$pilot = PilotData::getPilotData($pirep->pilotid);
		
		// Explode the flighttimes
		$flighttime = explode('.', $pirep->flighttime);
		$flighttime_min = ($flighttime[0] * 60) + ($flighttime[1]);
		$totalpay = ($pilot->payrate / 60) * $flighttime_min;
		
		echo '<h3>PIREP - #'.$pirepid.' & Pilot - '.$pilot->firstname.' '.$pilot->lastname.' Estimated Pay</h3>';
		echo $pirep->depicao.' => '.$pirep->arricao;
		echo '<br />';
		echo 'Payrate Per Hour: $'.$pilot->payrate;
		echo '<br />';
		echo '<strong>Estimated Pay for this flight - '.FinanceData::formatMoney($totalpay).'</strong>';
	}
	
	/***
	**
	**
	** Use this function to return the pilot's current pay in a view
	** Just pass in a ?pirepid=$pirep->pirepid value and it will return the pay correctly.
	** e.g. http://yourvaurl.com/index.php/PayChecker/getEstimatePay?pirepid=1
	**
	***/
	public function getEstimatePay() {
		$pirepid = DB::escape($this->get->pirepid);
		$sql = 'SELECT * FROM `'.TABLE_PREFIX.'pireps` WHERE `pirepid` = '.$pirepid;
		$pirep = DB::get_row($sql);
		
		$pilot = PilotData::getPilotData($pirep->pilotid);
		
		// Explode the flighttimes
		$flighttime = explode('.', $pirep->flighttime);
		$flighttime_min = ($flighttime[0] * 60) + ($flighttime[1]);
		$totalpay = ($pilot->payrate / 60) * $flighttime_min;
		
		return FinanceData::formatMoney($totalpay);
	}
}