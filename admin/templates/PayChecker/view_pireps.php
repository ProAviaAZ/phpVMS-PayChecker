<?php
if(!$allpilots) {
	echo 'No Pilots Found!';
} else {
?>
<h3>Check PIREPS</h3>
<h4><strong>Please Note:</strong> If the following "Last PIREP Pay" is $0.00 then it probably doesn't have a Ledger Value yet, so the PIREP will have to be rejected or accepted.</h4>
<table class="tablesorter" style="width: 100%; height: 100%;">
	<thead>
		<tr>
			<th>Pilot ID</th>
			<th>Pilot Name</th>
			<th>Last PIREP ID</th>
			<th>Last PIREP Apts</th>
			<th>Last PIREP Pay</th> <!-- What they have been payed -->
			<th>Last PIREP Real Pay</th> <!-- What they should have been payed -->
			<th>Pilot's Total Pay</th>
			<th>Options</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($allpilots as $pilot) {
		$lastpirep = PIREPData::getLastReports($pilot->pilotid, 1);
		$pilotcode = PilotData::getPilotCode($lastpirep->code, $lastpirep->pilotid);
		$ledger = LedgerData::getPaymentByPIREP($lastpirep->pirepid);
		$realpay = new PayChecker();
		$realpay = $realpay->getEstimatePay($lastpirep->pirepid);
	?>
		<tr>
			<td><?php echo $pilotcode; ?></td>
			<td><?php echo $pilot->firstname.' '.$pilot->lastname; ?></td>
			<td><a href="<?php echo SITE_URL; ?>/admin/index.php/pirepadmin/editpirep?pirepid=<?php echo $lastpirep->pirepid; ?>&pilotid=<?php echo $pilot->pilotid; ?>">#<?php echo $lastpirep->pirepid; ?></a></td>
			<td><?php echo $lastpirep->depicao.' => '.$lastpirep->arricao; ?></td>
			<td><?php echo FinanceData::formatMoney($ledger->amount); ?></td>
			<td><?php echo $realpay; ?></td>
			<td><?php echo FinanceData::formatMoney($pilot->totalpay); ?></td>
			<td><a href="<?php echo SITE_URL; ?>/admin/index.php/PayChecker/viewPilotPIREPS/<?php echo $pilot->pilotid; ?>">View PIREPS</a></td>
		</tr>
	<?php
	}
	?>
	</tbody>
</table>
<?php
}
?>
