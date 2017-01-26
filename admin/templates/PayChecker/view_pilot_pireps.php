<?php
if(!$allpireps) {
	echo 'No PIREPS Found!';
} else {
	$pilotcode = PilotData::getPilotCode($pilot->code, $pilot->pilotid);
?>
<h3>Viewing PIREPS for <?php echo $pilotcode.' - '.$pilot->firstname.' '.$pilot->lastname; ?></h3>
<table class="tablesorter" style="width: 100%; height: 100%;">
	<thead>
		<tr>
			<th>#ID</th>
			<th>DepICAO</th>
			<th>ArrICAO</th>
			<th>A/C</th>
			<th>Flighttime</th>
			<th>Date Submitted</th>
			<th>Status</th>
			<th>Last PIREP Pay</th> <!-- What they have been payed -->
			<th>Last PIREP Real Pay</th> <!-- What they should have been payed -->
			<th>Revenue</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($allpireps as $pirep) {		
		$ledger = LedgerData::getPaymentByPIREP($pirep->pirepid);
		$realpay = new PayChecker();
		$realpay = $realpay->getEstimatePay($pirep->pirepid);
	?>
	<tr>
		<td align="center">
			<a href="<?php echo url('/pireps/view/'.$pirep->pirepid);?>" target="_blank"><?php echo $pirep->code . $pirep->flightnum; ?></a>
		</td>
		<td align="center"><?php echo $pirep->depicao; ?></td>
		<td align="center"><?php echo $pirep->arricao; ?></td>
		<td align="center"><?php echo $pirep->aircraft . " ($pirep->registration)"; ?></td>
		<td align="center"><?php echo $pirep->flighttime; ?></td>
		<td align="center"><?php echo date(DATE_FORMAT, $pirep->submitdate); ?></td>
		<td align="center">
			<?php
			if($pirep->accepted == PIREP_ACCEPTED) {
				echo '<div id="success">Accepted</div>';
			} elseif($pirep->accepted == PIREP_REJECTED) {
				echo '<div id="error">Rejected</div>';
			} elseif($pirep->accepted == PIREP_PENDING) {
				echo '<div id="error">Approval Pending</div>';
			} elseif($pirep->accepted == PIREP_INPROGRESS) {
				echo '<div id="error">Flight in Progress</div>';
			}
			?>
		</td>
		<td align="center"><?php echo FinanceData::formatMoney($ledger->amount); ?></td>
		<td align="center"><?php echo $realpay; ?></td>
		<td align="left">
			<?php
			$gross = $pirep->price * $pirep->load;
			# $gross = $pirep->gross;
			$revenue = $gross - $pirep->expenses - $pirep->fuelprice - $ledger->amount;
			echo 'PIREP Gross: '.$pirep->gross;
			echo '<br />';
			echo 'Actual Gross: '.$gross;
			echo '<br />';
			echo 'Expenses: '.$pirep->expenses;
			echo '<br />';
			echo 'Fuel Price: '.FinanceData::formatMoney($pirep->fuelprice);
			echo '<br />';
			echo 'Pilot Pay: '.FinanceData::formatMoney($ledger->amount);
			echo '<hr />';
			echo '<strong>Actual Revenue: '.FinanceData::formatMoney($revenue).'</strong>';
			echo '<br />';
			echo '<strong>PIREP Revenue: '.FinanceData::formatMoney($pirep->revenue).'</strong>';
			?>
		</td>
	</tr>
	<?php
	}
	?>
	</tbody>
</table>
<?php
}
?>
