<?php
/*
 * Copyright (c) 2015 Jeroen van Marion <jeroen@vanmarion.nl>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */
 header("Refresh:30"); // refresh page every 30 sec. no need to shorten this
 
?>
<html>
	<head>
		<meta http-equiv="Content-Language" content="nl">
		<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
		<title>Solar Panel Dashboard</title>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
		<link rel="icon" href="favicon.ico" type="image/x-icon">
		<style>
body {
    background-color: #fff;
	font-size: 12px;
	font-family: Arial, Verdana;
}

td {
	font-size: 12px;
	font-family: arial, verdana, sans-serif;
	color: #000;
	}

h1 {
    color: maroon;
    margin-left: 40px;
} 
</style>
		
	</head>
<body>
<?php
require_once 'inc/conf.php';

echo "<b>System name:</b> " . $System_Name; // can gedefineerd worden in de conf.php
$conn = mysqli_connect($dbHost, $dbUserName, $dbUserPasswd, $dbName);
	if (!$conn) {
		die("Connection failed: " . mysqli_connect_error());
	}
echo "<table border='0'><tr>";

//zet values op 0 om waarde opnieuw te berekenen in de loop
$sumPower= 0; 
$sumVDC = 0;	
$sumAmps = 0; 
$sumKwh = 0;

define('COLS', $Nr_Colums); // max number of colums can be set in conf.php $Nr_Colums = 5;
$col = 0; // number of the last column filled
		
$output= mysqli_query($conn,"SELECT inverter_serial, panel_1, panel_2 FROM inverters");
if ($output->num_rows > 0) {
	while($row=mysqli_fetch_array($output)){
		$panel1 = $row['panel_1'];
		$panel2 = $row['panel_2'];
	
	// eerst alle serienummers ophalen van de inverters
		$inverter = $row['inverter_serial']; 
		
		$input = mysqli_query($conn,"SELECT * FROM enecsys WHERE id = $inverter ORDER BY ts DESC LIMIT 1");
		if ($input->num_rows > 0) {
			while ($row = mysqli_fetch_array($input)) {
				$col++;
				//show total inverters (results) from table inverters
				$row_cnt = $output->num_rows;
		
				/* fout afhandeling todo indien geen inverters gevonden */
			
				//calculate DCVolt for every inverter.
				$dcvolt = $row['dcpower'] / $row['dccurrent'];
				
				$accurrent = $row['dcpower'] / $row['acvolt'];
				
				/* SUM calculate for result for all inverters*/
				// calculate sum dc power (total)
				$sump_value = $row['dcpower'];
				$sumPower += $sump_value;  // echo $sumPower; voor total result per inverter from result
				
				$sumv_value = $row['dcpower'] / $row['dccurrent'];
				$sumVDC += $sumv_value;  // echo $sumVDC; voor total result per inverter from result

				//calculate sum dccurrent (total)
				$suma_value = $row['dccurrent'];
				$sumAmps += $suma_value;  // total Amps
				
				//calculate sum kWh (total)
				$sumk_value = $row['wh'] / 1000;
				$sumKwh += $sumk_value;  // echo $sumKwh; 
				
							
				//output for each inverter
				echo"<td bgcolor='#cccccc'><table>";
				echo "<tr><td>Inverter:</td><td>" .  $row['id']  . "</td></tr>" ;
				echo "<tr><td>Update:</td><td>" .  date('d-m-Y H:i:s', strtotime($row['ts']))  . "</td></tr>" ;
				echo "<tr><td>Power(Wh):</td><td>" .  $row['dcpower']  . "</td></tr>" ;
				echo "<tr><td>DCCurrent(Amps):</td><td>" .  $row['dccurrent']  . "</td></tr>" ;
				echo "<tr><td>ACCurrent(Amps):</td><td>" .  number_format($accurrent,2)  . "</td></tr>" ;
				echo "<tr><td>ACFreq:</td><td>" .  $row['acfreq']  . "</td></tr>" ;
				echo "<tr><td>DCVolt:</td><td>" .  number_format($dcvolt,2)  . "</td></tr>" ;
				echo "<tr><td>ACVolt:</td><td>" .  $row['acvolt']  . "</td></tr>" ;
				echo "<tr><td>Temp:</td><td>" .  $row['temp']  . "  &#8451;</td></tr>" ;
				echo "<tr><td>Efficiency:</td><td>" .  $row['efficiency']  . "</td></tr>" ;
				if ($row['state'] == 0) {
					echo "<tr><td>State:</td><td>0 - normal to grid</td></tr>" ;
				}
				else if ($row['state'] == 1) {
					echo "<tr><td>State:</td><td>1 - not enough light</td></tr>" ;
				}
				else if ($row['state'] == 3) {
					echo "<tr><td>State:</td><td>3 - other low light condition</td></tr>" ;
				}
				else {
					echo "<tr><td>State:</td><td>" .  $row['state']  . "</td></tr>" ;
				}
				//echo "<tr><td>State:</td><td>" .  $row['state']  . "</td></tr>" ;
				echo "<tr><td>Kwh:</td><td>" .  $row['wh'] / 1000 . "</td></tr>" ;
				echo "<tr><td>Panel 1:</td><td>" .  $panel1 . "</td></tr>" ;
				echo "<tr><td>Panel 2:</td><td>" .  $panel2 . "</td></tr>" ;
				
				// get date now to pull results from today
				$dtime = new DateTime();
				$new_date = $dtime->format('Y-m-d');
				
				$CurInvPower = mysqli_query($conn,"select id, max(wh) - min(wh) as nu from enecsys where ts like '%$new_date%' and id = $inverter");
				//$CurInvPower = mysqli_query($conn,"select id, max(wh) - min(wh) as nu from enecsys where ts like '%2015-04-25%' and id = $inverter");				
				if ($CurInvPower->num_rows > 0 ) {
					while ($row = mysqli_fetch_array($CurInvPower)) {
						if ($row['nu'] == '') {
							echo "<tr><td>Nu(Watt):</td><td>No data</td></tr>" ;
						}
						else {
							echo "<tr><td>Nu(Watt):</td><td>" .  $row['nu']  . "</td></tr>" ;
						}
					}
				}
								
				echo "</table>";
				$col ;
				echo "</td><td width='10px'</td>";
	
				// check if max colums are reached. Yes -> new row. No -> do nothing
				if ($col == COLS) {
					$col = 0; 
					echo "</tr><tr><td height='10px'></td></tr><tr>"; // start a new one
				}	
			}
		}
		else {
			echo "No data in log table";
		}
	}
}
else {
	echo "Inverter table is empty";
}
		
			
echo "</tr></table>";
echo  "Totaal aantal inverters: " . $row_cnt . " | Power: " . $sumPower . " Watt / " . " | Total VDC: " .  number_format($sumVDC,2)  . " | Total Amps: "  . $sumAmps . " | Total Kwh: " . $sumKwh ;
echo "<br><br>PVOutput Enecsys Team page: <a href='http://www.pvoutput.org/ladder.jsp?tid=1018' target='_blank'>Enecsys by Tweakers</a>";
mysqli_close($conn);
?>
</body>
</html>
