<?php
session_start();
include("Cruise.php");
$cruise = new Cruise;

// grab the action from the url
$action = isset($_GET['action']) ? $_GET['action'] : '';

// grab the id from the url
$id = isset($_GET['Id']) ? $_GET['Id'] : '';

// check if we need to inject javascript for the datepicker
if ($action == "SearchDate") {
	$cruise->printHeader("Find a cruise",'  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script>
  $(function() {
    $( "#from" ).datepicker({
      defaultDate: "+1w",
      changeMonth: true,
      numberOfMonths: 3,
	  dateFormat: "yy-mm-dd",
      onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate );
      }
    });
    $( "#to" ).datepicker({
      defaultDate: "+1w",
      changeMonth: true,
      numberOfMonths: 3,
	  dateFormat: "yy-mm-dd",
      onClose: function( selectedDate ) {
        $( "#from" ).datepicker( "option", "maxDate", selectedDate );
      }
    });
  });
  </script>');
}
else {
	$cruise->printHeader();
}

// preform the specified task
switch ($action) {
		
	case "Ship" :
		// this section pulls information about the ship
		$ship = $cruise->buildSingle("SELECT * FROM Ship WHERE ShipId=:shipid",$id,":shipid");
		echo "<p>This is the template for the Ship Page.</p>

<p>Name : {$ship['Name']}</p>

<p>Date : {$ship['EnterDate']}</p>

<p>Description : {$ship['Description']}";

		// this pulls information about the ship amenities.
		$cruise->buildList("SELECT AmenityId,Name FROM Amenity WHERE AmenityId IN (SELECT Amenity_AmenityID FROM AmenityShip WHERE Ship_ShipId=:shipid)",$id,":shipid","Amenities on this ship","index.php?action=Amenity&Id=","AmenityId","Name");
		
		// this pulls information about the voyages this ship is scheduled for
		$cruise->buildList("SELECT VoyageId,Title FROM Voyage WHERE Ship_ShipId=:shipid",$id,":shipid","Voyages on this ship","index.php?action=Voyage&Id=","VoyageId","Title");
		
		break;
		
	case "Voyage" :
		// this section pulls information about the voyage
		$voyage = $cruise->buildSingle("SELECT * FROM Voyage WHERE VoyageId=:voyageid",$id,":voyageid");
		echo "<p>This is the template for the Voyage Page.</p>

<p>Title : {$voyage['Title']}</p>

<p>Departure Date : {$voyage['DepartureDate']}</p>

<p>Return Date : {$voyage['ReturnDate']}</p>

<p>Description : {$voyage['Description']}</p>";
		
		// this pulls information about the ship
		$ship = $cruise->buildSingle("SELECT ShipId,Name FROM Ship WHERE ShipId=:shipid",$voyage['Ship_ShipId'],":shipid");
		echo "<p>Ship Name : <a href='index.php?action=Ship&Id={$ship['ShipId']}'>{$ship['Name']}</a></p>";
	
		// this pulls information about the scheduled excursions
		$cruise->buildList("SELECT ExcursionScheduled.ExcursionScheduledId,ExcursionScheduled.Excursion_ExcursionId,Excursion.Name FROM ExcursionScheduled INNER JOIN Excursion ON ExcursionScheduled.Excursion_ExcursionId=Excursion.ExcursionId WHERE ExcursionScheduled.VoyagePorts_VoyagePortId IN (SELECT VoyagePortId FROM VoyagePorts WHERE Voyage_VoyageID=:voyageid)",$id,":voyageid","Excursions on this Voyage","index.php?action=ExcursionScheduled&Id=","ExcursionScheduledId","Name");
		
		// this pulls information about the ports this voyage stops at
		$cruise->buildList("SELECT VoyagePorts.VoyagePortId,Port.Name FROM VoyagePorts INNER JOIN Port ON VoyagePorts.Port_PortId = Port.PortId WHERE VoyagePorts.Voyage_VoyageId=:voyageid",$id,":voyageid","Ports this voyage stops at","index.php?action=VoyagePort&Id=","VoyagePortId","Name");		
		
		// dispaly a link that allows user to book a cruise
		echo "<p><a href='index.php?action=preBookVoyageCabinClass&Id={$voyage['VoyageId']}'>Book this voyage</a></p>";
		
		break;
		
	case "Excursion" :
		// this section pulls information about the excursion
		$excursion = $cruise->buildSingle("SELECT Excursion.*,ExcursionProvider.Name AS ProviderName FROM Excursion INNER JOIN ExcursionProvider ON Excursion.ExcursionProvider_ExcursionProviderId = ExcursionProvider.ExcursionProviderId WHERE Excursion.ExcursionId=:excursionid",$id,":excursionid");
		echo "<p>This is the template for the Excursion Page.</p>

<p>Title : {$excursion['Name']}</p>

<p>Provider : <a href='index.php?action=ExcursionProvider&Id={$excursion['ExcursionProvider_ExcursionProviderId']}'>{$excursion['ProviderName']}</a></p>";

		// this section pulls information about the scheduled dates for this excursion
		$cruise->buildList("SELECT ExcursionScheduled.ExcursionScheduledId,Voyage.VoyageId,Voyage.Title FROM ExcursionScheduled INNER JOIN VoyagePorts ON ExcursionScheduled.VoyagePorts_VoyagePortId = VoyagePorts.VoyagePortId INNER JOIN Voyage ON VoyagePorts.Voyage_VoyageId = Voyage.VoyageId WHERE ExcursionScheduled.Excursion_ExcursionId=:excursionid",$id,":excursionid","Book this excursion on the following voyages","index.php?action=ExcursionScheduled&Id=","ExcursionScheduledId","Title");
		break;
		
	case "ExcursionScheduled" :
		$excursionscheduled = $cruise->buildSingle("SELECT ExcursionScheduled.*,Excursion.*,ExcursionProvider.Name AS ProviderName FROM ExcursionScheduled INNER JOIN Excursion ON ExcursionScheduled.Excursion_ExcursionId = Excursion.ExcursionId INNER JOIN ExcursionProvider ON Excursion.ExcursionProvider_ExcursionProviderId = ExcursionProvider.ExcursionProviderId WHERE ExcursionScheduled.ExcursionScheduledId=:excursionscheduledid",$id,":excursionscheduledid");
		echo "<p>This is the template for the Excursion Scheduled Page.</p>

<p>Title : {$excursionscheduled['Name']}</p>

<p>Provider : <a href='index.php?action=ExcursionProvider&Id={$excursionscheduled['ExcursionProvider_ExcursionProviderId']}'>{$excursionscheduled['ProviderName']}</a></p>

<p>Start Date/Time : {$excursionscheduled['Date']}</p>

<p>Cost : \${$excursionscheduled['Cost']}</p>

<p>Available Spots: {$excursionscheduled['AvailableSpots']}</p>";	
		break;
		
	case "Port" :
		$port = $cruise->buildSingle("SELECT * FROM Port WHERE PortId=:portid",$id,":portid");
		echo "<p>This is the template for the Port Page.</p>

<p>Name : {$port['Name']}</p>

<p>Location : {$port['Location']}</p>

<p>Description : {$port['Description']}</p>";

		// build a list of excursions at this port
		$cruise->buildList("SELECT ExcursionId,Name FROM Excursion WHERE Port_PortId=:portid",$id,":portid","Excursions at this port","index.php?action=Excursion&Id=","ExcursionId","Name");
		
		// build a list of voyages that stop at this port
		$cruise->buildList("SELECT Voyage.Title,Voyage.VoyageId FROM Voyage INNER JOIN VoyagePorts ON Voyage.VoyageId = VoyagePorts.Voyage_VoyageId WHERE VoyagePorts.Port_PortId=:portid",$id,"portid","Voyages that stop at this port","index.php?action=Voyage&Id=","VoyageId","Title");
		
		break;
		
	case "VoyagePort" :
		$voyageport = $cruise->buildSingle("SELECT VoyagePorts.*,Port.*,Voyage.Title FROM VoyagePorts INNER JOIN Port ON VoyagePorts.Port_PortId = Port.PortId INNER JOIN Voyage ON VoyagePorts.Voyage_VoyageId = Voyage.VoyageId WHERE VoyagePorts.VoyagePortId=:voyageportid",$id,":voyageportid");
		echo "<p>This is the template for the Voyage Port Page.</p>

<p>Name : {$voyageport['Name']}</p>

<p>Location : {$voyageport['Location']}</p>

<p>Voyage : <a href='index.php?action=Voyage&Id={$voyageport['Voyage_VoyageId']}'>{$voyageport['Title']}</a></p>

<p>Description : {$voyageport['Description']}</p>

<p>Arrival Date : {$voyageport['ArrivalDate']}</p>

<p>Departure Date : {$voyageport['DepartureDate']}</p>";
		
		// build a list of all excursions scheduled for this port of call
		$cruise->buildList("SELECT ExcursionScheduled.ExcursionScheduledId,Excursion.ExcursionId,Excursion.Name FROM ExcursionScheduled INNER JOIN Excursion ON ExcursionScheduled.Excursion_ExcursionId = Excursion.ExcursionId WHERE ExcursionScheduled.VoyagePorts_VoyagePortId=:voyageportid",$id,":voyageportid","Excursions Scheduled at this stop","index.php?action=ExcursionScheduled&Id=","ExcursionScheduledId","Name");
		
		
		break;
		
	case "ExcursionProvider" :
		$provider = $cruise->buildSingle("SELECT * FROM ExcursionProvider WHERE ExcursionProviderId=:excursionproviderid",$id,":excursionproviderid");
		echo "<p>This is the template for the Excursion Provider Page.</p>

<p>Excursion Provider : {$provider['Name']}</p>

<p>Location : {$provider['Location']}</p>

<p>Phone Number : {$provider['PhoneNumber']}</p>";

		// build a list of excursions from this provider
		$cruise->buildList("SELECT ExcursionId,Name FROM Excursion WHERE ExcursionProvider_ExcursionProviderId=:excursionproviderid",$id,":excursionproviderid","Excursions offered by this provider","index.php?action=Excursion&Id=","ExcursionId","Name");

		break;
		
		
	case "SearchDate" :
		echo '<form action="index.php?action=doSearchDate" method="post">
<p>Date Range : <input type="text" id="from" name="from"> - 
<input type="text" id="to" name="to"></p>
<input type="submit"><input type="reset">
</form>';
		
		
		break;
		
	case "doSearchDate" :
		$cruise->doSearchDate($_POST['from'],$_POST['to']);
	
		break;
		
	case "SearchPrice" :
		echo '<form action="index.php?action=doSearchPrice" method="post">
<p>Price Range : <input type="text" id="low" name="low"> - 
<input type="text" id="high" name="high"></p>
<input type="submit"><input type="reset">
</form>';
		
		
		break;
		
	case "doSearchPrice" :
		$cruise->doSearchPrice($_POST['low'],$_POST['high']);
	
		break;
		
	case "Login" :
		echo '<form action="index.php?action=doLogin" method="post">
<p>Email Address : <input type="text" name="email"></p>
<p>Password : <input type="password" name="password"></p>
<p><input type="submit"><input type="reset"></p>
</form>';

		echo "<p><a href='index.php?action=Register'>Create a new account</a></p>";
	
		break;
		
	case "doLogin" :
		// get the username and password from the form and send to doLogin
		$cruise->doLogin($_POST['email'],$_POST['password']);
		break;
		
	case "Register" :
		echo '<form action="index.php?action=doRegister" method="post">
<p>Email Address : <input type="text" name="email"></p>
<p>Password : <input type="password" name="password"></p>
<p>Family Password : <input type="password" name="familypassword"></p>
<p>First Name : <input type="text" name="first"></p>
<p>Last Name : <input type="text" name="last"></p>
<p>Address : <textarea name="address"></textarea></p>
<p>Phone Number : <input type="text" name="phone"></p>
<p>Passport Number : <input type="text" name="passport"></p>
<p>Credit Card Number : <input type="text" name="creditcard"></p>
<p><input type="submit"><input type="reset"></p>
</form>';

	
		break;
			
	case "doRegister" :
		// get all of the data from the forms and send to doRegister
		$cruise->doRegister($_POST['email'],$_POST['password'],$_POST['familypassword'],$_POST['first'],$_POST['last'],$_POST['phone'],$_POST['passport'],$_POST['address'],$_POST['creditcard']);	
		break;
		
	case "Logout" :
		if (session_destroy()) {
			echo "You Have logged out.";
		}
		else {
			echo "Error logging out please contact support";
		}
		
		// redirect to homepage
		$cruise->redirect("index.php");
		
		break;
		
	case "editUser" :
		// make sure user is logged in
		$cruise->LoggedIn();
		
		$row = $cruise->buildSingle("SELECT EmailAddress, FirstName, LastName, Address, TelephoneNumber, PassportNumber, CreditCardNumber FROM Customer where CustomerId=:id",$_SESSION['customerid'],":id");
		
		echo '<form action="index.php?action=doEditUser" method="post">
<p>Email Address : <input type="text" name="email" value="' . $row['EmailAddress'] .'"></p>
<p>First Name : <input type="text" name="first" value="' . $row['FirstName'] .'"></p>
<p>Last Name : <input type="text" name="last" value="' . $row['LastName'] .'"></p>
<p>Address : <textarea name="address">' . $row['Address'] .'</textarea></p>
<p>Phone Number : <input type="text" name="phone" value="' . $row['TelephoneNumber'] .'"></p>
<p>Passport Number : <input type="text" name="passport" value="' . $row['PassportNumber'] .'"></p>
<p>Credit Card Number : <input type="text" name="creditcard" value="' . $row['CreditCardNumber'] .'"></p>
<p><input type="submit"><input type="reset"></p>
</form>';
		
		break;
		
	case "doEditUser" : 
		// make sure user is logged in
		$cruise->LoggedIn();
		$cruise->doUpdateUser($_POST['email'],$_POST['first'],$_POST['last'],$_POST['phone'],$_POST['passport'],$_POST['address'],$_POST['creditcard']);
		
		
		break;
		
	case "createRelationshipLink" :
		// make sure user is logged in
		$cruise->LoggedIn();
		
		echo "<p>Choose the relationship you would like to add to your account</p>" .
			 '	<form action="index.php?action=doCreateRelationshipLink" method="post">
		<p><select name="relationship">
        	<option value="Spouse">Spouse</option>
            <option value="Mother">Mother</option>
            <option value="Father">Father</option>
            <option value="Son">Son</option>
            <option value="Daughter">Daughter</option>
            <option value="Extended Family">Extended Family</option>
            <option value="Travel Buddy">Travel Buddy</option>
		</select></p>
		<p><input type="submit"><input type="reset"></p>
	</form>';
		
		break;
	
	case "doCreateRelationshipLink" :
		// make sure user is logged in
		$cruise->LoggedIn();
		echo "<p>Send this link to the user you want to add to your cruise family. They must be logged into their account and provide the correct family password." .
			 "If you entered a family password when you created your second account there is no need to enter it again.</p>" .
			 "<p><form><input type='text' size='80' value='" . $cruise->root_url . "index.php?action=addRelationship&Id=" . $_SESSION['customerid'] . 
			 "&Relationship=" . $_POST['relationship'] . "'></form></p>";
		break;
		
	case "addRelationship" :
		// make sure user is logged in
		$cruise->LoggedIn();
		
		$relationship = isset($_GET['Relationship']) ? $_GET['Relationship'] : '';
		$row = $cruise->buildSingle("SELECT FirstName,LastName FROM Customer WHERE CustomerId = :id",$id,":id");
		echo "<p>Confirm that you would like to add " . $row['FirstName'] . " " . $row['LastName'] . " as your " . $relationship . ".</p>" .
			 "<p><a href='index.php?action=doAddRelationship&Id=" . $id . "&Relationship=" . $relationship . "'>Confirm</a> " . 
			 "<a href='index.php?action=userHome'>Cancel</a></p>"; 
		break;
		
	case "doAddRelationship" :
		// make sure user is logged in
		$cruise->LoggedIn();
		
		$relationship = isset($_GET['Relationship']) ? $_GET['Relationship'] : '';
		if ($relationship != '' && $id != '' ) {
			$cruise->doAddRelationship($_SESSION['customerid'],$id,$relationship);
		}
		
		// redirect to user homepage
		$cruise->redirect("index.php?action=userHome");
		break;
		
	case "preBookVoyageCabinClass" :
		// make sure user is logged in
		$cruise->LoggedIn("<p>You must be logged in to book a cruise, please login or register a new account</p>");
		
		echo "<p>Select which class of cabin you would like to book for this voyage. Information on the cabin classes can be found on the ship page.</p>" .
			 "<p><form action='index.php?action=preBookVoyageCabin' method='post'><input type='hidden' name='voyageid' value='" . $id . "'>";
		
		$cruise->buildDropDown("SELECT CabinClass.*, VoyagePricing.Price FROM CabinClass INNER JOIN VoyagePricing ON CabinClass.CabinClassId = VoyagePricing.CabinClass_CabinClassId WHERE VoyagePricing.Voyage_VoyageId=:voyageid","cabinclass",$id,":voyageid","CabinClassId","CabinClassName","Price"," - $");
		
		echo "<input type='submit'><input type='reset'></form></p>";
		
		break;
		
	case "preBookVoyageCabin" :
		// make sure user is logged in
		$cruise->LoggedIn();
		
		echo "<p>Select which cabin number you would like to book for this voyage. Ship layout and cabin locations can be viewed on the ship page.</p>" .
			 "<p><form action='index.php?action=preBookVoyageOccupancy' method='post'><input type='hidden' name='voyageid' value='" . $_POST['voyageid'] . "'>";
		
		$cruise->buildDropDown("SELECT Cabin.CabinId FROM Cabin INNER JOIN Voyage ON Cabin.Ship_ShipId = Voyage.Ship_ShipId WHERE (Cabin.CabinClass_CabinClassId = :cabinclass) AND (Voyage.VoyageId=:voyageid) AND NOT EXISTS (SELECT 1 FROM VoyageTicketed WHERE Cabin.CabinID = VoyageTicketed.Cabin_CabinId)","cabin",$_POST['voyageid'],":voyageid","CabinId","CabinId","","",$_POST['cabinclass'],":cabinclass");
		
		echo "<input type='submit'><input type='reset'></form></p>";
	
		break;
		
	case "preBookVoyageOccupancy" :
		// make sure user is logged in
		$cruise->LoggedIn();
		echo "<p>Choose which family members you want to book in this cabin</p><form action='index.php?action=bookVoyageConfirm' method='post'>" . 
			 "<input type='hidden' name='voyageid' value='" . $_POST['voyageid'] . "'><input type='hidden' name='cabin' value='" . $_POST['cabin'] . "'>";
		$cruise->buildRadios("SELECT Customer.FirstName, Customer.LastName, Customer.CustomerId FROM Customer INNER JOIN CustomerRelationship ON Customer.CustomerId = CustomerRelationship.Customer_CustomerId2 WHERE CustomerRelationship.Customer_CustomerId1=:customerid",$_SESSION['customerid'],":customerid","FirstName","CustomerId","LastName"," ");
		echo '<p><input type="submit"><input type="reset"></p></form>';
		break;
		
	case "bookVoyageConfirm" :
		// make sure user is logged in
		$cruise->LoggedIn();
		$cruise->bookVoyageConfirm($_SESSION['customerid'],$_POST['voyageid'],$_POST['cabin']);
	
		break;
		
	case "doBookVoyage" :
		// make sure user is logged in
		$cruise->LoggedIn();
		$cruise->doBookVoyage($_SESSION['customerid'],$_POST['voyageid'],$_POST['cabin']);
	
		
	
		break;
		
	case "userHome" : 
		$cruise->LoggedIn();
		echo "<p>User homepage template goes here</p>" .
			 "<p><a href='index.php?action=editUser'>Update Personal Information</a></p>" .
			 "<p>Change user/family password</p>" . 
			 "<p><a href='index.php?action=createRelationshipLink'>Add Family Member/Travler</a></p>" .
			 "<p>List of family members, and links to remove relationship goes here</p>" .
			 "<p>List of cruises customer has been ticketed for</p>";
		
		break;
		
	default :
		echo "Welcome to Blue Barracuda Cruises!";
		break;
		
}

$cruise->printFooter();

?>