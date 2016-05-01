<?php
/*
	Cruise Class
	
	This class provides functions to generate various webpages for the cruise website
	it also creates a new database object to allow access to mysql data

*/

include"dbClass.php";

class Cruise{
	
	// database configuration
	private $host      = "localhost";
    private $user      = "cruise";
    private $pass      = "bluebarracuda";
    private $db_name   = "CruiseLine";
	
	public $db;
	public $root_url = "htttp://kclarke.com/cruise/";
	
	// default constructor 
    public function __construct(Database $database = null){
        // create a new database object
		if (!$database) {
            $database = new Database($this->host,$this->user,$this->pass,$this->db_name);
        }
        $this->db = $database;
    }
	
	// this function prints the html for the header and calls buildMenu to display the nav bar
	public function printHeader($title = "Blue Barracuda Cruises",$headtag = "") {
		// add a link to login or log out based on if the user is logged in or out
		$loggedin = isset($_SESSION['loggedin']) ? $_SESSION['loggedin'] : FALSE;
		if ($loggedin == TRUE) {
			$header_action = "Logout";
		} else {
			$header_action = "Login";
		}
		
		echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Ek+Mukta">
<link rel="stylesheet" type="text/css" href="cruise.css">
<title>' .  $title . '</title>' . $headtag . '</head>
<body>
<a href="index.php"><img src="fish.png" alt="Blue Barracuda Logo"/></a>
<div class="menu-wrap">
    <nav class="menu">
        <ul class="clearfix">
        	<li><a href="index.php?action=Search">Search</a>
				<ul class="sub-menu"><li><a href="index.php?action=SearchDate">By Date</a></li>
				<li><a href="index.php?action=SearchPrice">By Price</a></li></ul></li>';
			
		// this block of code generates the navigation menu at the top
		$this->buildMenu("Ship","Ships","ShipId","Name");
		$this->buildMenu("Port","Ports","PortId","Name");
		$this->buildMenu("Voyage","Voyages","VoyageId","Title");
		$this->buildMenu("Excursion","Excursions","ExcursionId","Name");

		echo '	<li><a href="index.php?action=' . $header_action . '">' . $header_action . '</a></li>
		</ul>
    </nav>
</div>
<div>';

	}
	
	public function printFooter() {
		
		echo '</div>
</body>
</html>';	
	}
	
	// this function builds the menu items for the navigation bar
	public function buildMenu($table_name,$title,$id_name,$display_name) {

		echo '<li><a href="#">' . $title . '<span class="arrow">&#9660;</span></a>
	<ul class="sub-menu">';

		$this->db->query("SELECT " . $id_name . ',' . $display_name . ' FROM ' . $table_name);
		$rows = $this->db->resultset();

		foreach($rows as $row) {
			echo '<li><a href="index.php?action=' . $table_name . '&Id=' . $row[$id_name] . '">' . $row[$display_name] . "</a></li>\r\n";
		}
		
		echo "</ul></li>\r\n";
	}
	
	// this function executes a query for a single row and returns the data
	public function buildSingle($query,$bind_id,$bind_id_name) {
		$this->db->query($query);
		$this->db->bind($bind_id_name,$bind_id);
		return $this->db->single();
	}

	// this function builds a list of the items in the query
	public function buildList($query,$bind_id,$bind_id_name,$title,$link,$id_name,$display_name) {
		echo '<p>' . $title . '<ul>';
		$this->db->query($query);
		$this->db->bind($bind_id_name,$bind_id);
		$rows = $this->db->resultset();
		foreach($rows as $row) {
			echo '<li><a href="' . $link . $row[$id_name] . '">' . $row[$display_name] . "</a></li>\r\n";
		}
		echo "</ul></p>\r\n";
	}
	
	public function buildDropDown($query,$name,$bind_id,$bind_id_name,$value_name,$display_name,$second_display = '',$second_pre_display = '',$second_bind_id = '',$second_bind_id_name = '') {
		echo "<select name='" . $name . "'>";
		$this->db->query($query);
		$this->db->bind($bind_id_name,$bind_id);
		if ($second_bind_id != '' && $second_bind_id_name != '') {
			$this->db->bind($second_bind_id_name,$second_bind_id);
		}
		
		$rows = $this->db->resultset();
		foreach($rows as $row) {
			echo '<option value="' . $row[$value_name] . '">' . $row[$display_name];
			if ($second_display != '') {
				echo $second_pre_display . $row[$second_display];
			}
			echo "</option>\r\n";
		}
		
		echo "</select>";
	}
	
	public function buildRadios($query,$bind_id,$bind_id_name,$display_name,$var_name,$second_display = '',$second_pre_display = '') {
		$this->db->query($query);
		$this->db->bind($bind_id_name,$bind_id);
		
		$rows = $this->db->resultset();
		foreach($rows as $row) {
			echo '<p><input type="radio" name="fam' . $row[$var_name] . '" value="booked">' . $row[$display_name];
			if ($second_display != '') {
				echo  $second_pre_display . $row[$second_display];
			}
			echo "</p>\r\n";
		}
	
	}
	
	// this function handles the user authentication
	public function doLogin($email,$password) {
		
		$this->db->query("SELECT CustomerId,Salt,Password,FirstName,LastName FROM Customer WHERE EmailAddress=:email");
		$this->db->bind(":email",$email);
		$user = $this->db->single();
		
		// salt and hash the password for security
		$hashedpw = hash('sha256', $password . $user['Salt']);
		
		if ($hashedpw == $user['Password']) {
			echo "<p>Login Successfull!</p>";
			$_SESSION['loggedin'] = TRUE;
			$_SESSION['email'] = $email;
			$_SESSION['first'] = $user['FirstName'];
			$_SESSION['last'] = $user['LastName'];
			$_SESSION['customerid'] = $user['CustomerId'];
			
			// redirect to the userHome
			$this->redirect("index.php?action=userHome");
		}
		else {
			echo "<p>Error on login please contact support</p>.";
			$this->redirect("index.php",15);
		}
		
	}
	
	// this function registers a user
	public function doRegister($email,$password,$familypassword,$first,$last,$phone,$passport,$address,$creditcard) {
		// verify all input
		// this could be done with java before the form is submitted
		
		// check for duplicate email address
		$this->db->query("SELECT * FROM Customer WHERE EmailAddress=:email");
		$this->db->bind(":email",$email);
		if ($user = $this->db->single()) {
			echo "Error, email already exists in database";	
		}
		else {
			$this->db->query("INSERT INTO Customer (CustomerId, Password, FamilyPassword, Salt, EmailAddress, FirstName, LastName, Address, TelephoneNumber, PassportNumber, CreditCardNumber) VALUES (NULL,:password,:familypassword,:salt,:email,:first,:last,:address,:phone,:passport,:creditcard)");
			// generate salt
			$salt = bin2hex(mcrypt_create_iv(32,MCRYPT_DEV_URANDOM));
			
			// hash password
			$hashedpw = hash('sha256',$password . $salt);
			
			// hash family password
			$hashedfampw = hash('sha256',$familypassword);
			
			// bind all the parameters
			$this->db->bind(":password",$hashedpw);
			$this->db->bind(":familypassword",$hashedfampw);
			$this->db->bind(":salt",$salt);
			$this->db->bind(":email",$email);
			$this->db->bind(":first",$first);
			$this->db->bind(":last",$last);
			$this->db->bind(":address",$address);
			$this->db->bind(":phone",$phone);
			$this->db->bind(":passport",$passport);
			$this->db->bind(":creditcard",$creditcard);
			
			// execute statement
			$this->db->execute();	
			
			echo "<p>Account Creation Successfull!</p>";
			
			// log the user in
			$_SESSION['loggedin'] = TRUE;
			$_SESSION['email'] = $email;
			$_SESSION['first'] = $first;
			$_SESSION['last'] = $last;
			
			// we need to pull the users id from the database as it is generated when inserted			
			$this->db->query("SELECT CustomerId FROM Customer WHERE EmailAddress=:email");
			$this->db->bind(":email",$email);
			$row = $this->db->single();
			$_SESSION['customerid'] = $row['CustomerId'];
			
			// redirect to the userHome
			$this->redirect("index.php?action=userHome");
			
		}
	}
	
		// this function updates a users information
	public function doUpdateUser($email,$first,$last,$phone,$passport,$address,$creditcard) {
		// verify all input
		// this could be done with java before the form is submitted
		
		$this->db->query("Update Customer SET EmailAddress=:email, FirstName=:first, LastName=:last, Address=:address, TelephoneNumber=:phone, PassportNumber=:passport, CreditCardNumber=:creditcard WHERE CustomerId=:customerid");
			
		// bind all the parameters
		$this->db->bind(":customerid",$_SESSION['customerid']);
		$this->db->bind(":email",$email);
		$this->db->bind(":first",$first);
		$this->db->bind(":last",$last);
		$this->db->bind(":address",$address);
		$this->db->bind(":phone",$phone);
		$this->db->bind(":passport",$passport);
		$this->db->bind(":creditcard",$creditcard);
		
		// execute statement
		$this->db->execute();	
		
		echo "<p>Personal Information Updated!</p>";
		
		$this->redirect("index.php?action=userHome");

	}
	
	// this function makes sure a user is logged in.
	// call $this->loggedIn() before any code that requires
	// the user information to be stored in the session
	public function LoggedIn($message = '') {
		if (! isset($_SESSION['loggedin']))  {
			$_SESSION['loggedin'] = FALSE;
		}
		if ($_SESSION['loggedin'] == FALSE) {
			$this->redirect("index.php?action=Login",$message);
			$this->printFooter();
			exit();
		}
			
	}
	
	public function doSearchDate($to,$from) {
		echo '<p>Voyages Leaving and Returning Between ' . $to . ' - ' . $from . '</p><ul>';
		
		$this->db->query("SELECT * FROM Voyage where DepartureDate > :to AND ReturnDate < :from");
		$this->db->bind(":to",$to);
		$this->db->bind(":from",$from);
		$rows = $this->db->resultset();
		
		foreach($rows as $row) {
			echo '<li><a href="index.php?action=Voyage&Id=' . $row['VoyageId'] . '">' . $row['Title'] . 
				 "</a><ul>" . '<li>Departure Date : ' . $row['DepartureDate'] .  '</li><li>Return Date : ' .
				 $row['ReturnDate'] . "</li></ul></li>\r\n";
		}
		echo "</ul></p>\r\n";
	}
	
	public function doSearchPrice($low,$high) {
		echo '<p>Voyages Priced Between $' . $low . ' - $' . $high . '</p><ul>';
			
		$this->db->query("SELECT Voyage.*,VoyagePricing.Price,CabinClass.CabinClassName FROM Voyage INNER JOIN VoyagePricing ON Voyage.VoyageId = VoyagePricing.Voyage_VoyageId INNER JOIN CabinClass ON VoyagePricing.CabinClass_CabinClassId = CabinClass.CabinClassId WHERE VoyagePricing.Price > :low AND VoyagePricing.Price < :high ORDER BY Voyage.VoyageId");
		$this->db->bind(":low",$low);
		$this->db->bind(":high",$high);
		$rows = $this->db->resultset();
		
		$previous_voyage_id = '';
		$first_run = TRUE;
		
		foreach($rows as $row) {
			if ($previous_voyage_id != $row['VoyageId']) {
				if ($first_run == FALSE) {
					echo '</li></ul></li>';
				}
				$first_run = FALSE;
				echo '<li><a href="index.php?action=Voyage&Id=' . $row['VoyageId'] . '">' . $row['Title'] . '</a><ul>';
			}
			$previous_voyage_id = $row['VoyageId'];
			echo '<ul>' . '<li>Cabin Class : ' . $row['CabinClassName'] .  '</li><li>Price : ' .
				 $row['Price'] . "</li></ul>\r\n";
		}
		echo "</li></ul></p>\r\n";
	}
	
	public function doBookExcursion($excursionid) {
		
	}
	
	public function doAddRelationship($userid1,$userid2,$relationship,$paid) {
		$this->db->query("INSERT INTO CustomerRelationship (Customer_CustomerId1, Customer_CustomerId2, Relationship) VALUES (:customerid1, :customerid2, :relationship)");
		$this->db->bind(":customerid1",$userid1);
		$this->db->bind(":customerid2",$userid2);
		$this->db->bind(":relationship",$relationship);
		
		// execute statement
		$this->db->execute();
			
	}
	
	public function bookVoyageConfirm($customerid,$voyageid,$cabin) {
		$this->db->query("SELECT Customer.FirstName, Customer.LastName, Customer.CustomerId FROM Customer INNER JOIN CustomerRelationship ON Customer.CustomerId = CustomerRelationship.Customer_CustomerId2 WHERE CustomerRelationship.Customer_CustomerId1=:customerid");
		$this->db->bind(":customerid",$customerid);
		$rows = $this->db->resultset();
		$passengers = 1;
		
		$output = "<form action='index.php?action=doBookVoyage' method='post'>" .
			 "<input type='hidden' name='voyageid' value='" . $voyageid . "'>" .
			 "<input type='hidden' name='cabin' value='" . $cabin . "'>" .
			 "<p>Passenger List</p><p>" . $_SESSION['first'] . " " . $_SESSION['last'] . "";
		
		foreach($rows as $row) {
			if ($_POST['fam' . $row['CustomerId']] == 'booked') {
				$passengers++;
				$output = $output . "<br>" . $row['FirstName'] . " " . $row['LastName'] . 
					 "<input type='hidden' name='fam" . $row['CustomerId'] . "' value='booked'>";
			}
		}
		
		if ($passengers < 2) {
			$this->redirect("index.php","You must book tickets for at least 2 passengers per cabin.");
		}
		
		echo $output .  "</p><p><input type='submit'><input type='reset'></form></p>";
		
		
	}
	
	public function doBookVoyage($customerid,$voyageid,$cabin,$paid = 0) {	
		// create the VoyageTicketed for the user making the booking
		$this->db->query("INSERT INTO VoyageTicketed (Customer_CustomerId, Voyage_VoyageId, Cabin_CabinId, Paid) VALUES (:customerid, :voyageid, :cabin, :paid)");
		$this->db->bind(":customerid",$customerid);
		$this->db->bind(":voyageid",$voyageid);
		$this->db->bind(":cabin",$cabin);
		$this->db->bind(":paid",$paid);
				
		// execute statement
		$this->db->execute();
		
		
		$this->db->query("SELECT Customer.FirstName, Customer.LastName, Customer.CustomerId FROM Customer INNER JOIN CustomerRelationship ON Customer.CustomerId = CustomerRelationship.Customer_CustomerId2 WHERE CustomerRelationship.Customer_CustomerId1=:customerid");
		$this->db->bind(":customerid",$customerid);
		$rows = $this->db->resultset();
		
		// for each family memeber we check to see if they were selected during the previous page
		foreach($rows as $row) {
			if ($_POST['fam' . $row['CustomerId']] == 'booked') {
				$this->db->query("INSERT INTO VoyageTicketed (Customer_CustomerId, Voyage_VoyageId, Cabin_CabinId, Paid) VALUES (:customerid, :voyageid, :cabin, :paid)");
				$this->db->bind(":customerid",$row['CustomerId']);
				$this->db->bind(":voyageid",$voyageid);
				$this->db->bind(":cabin",$cabin);
				$this->db->bind(":paid",$paid);
				
				// execute statement
				$this->db->execute();		
			}
		}
		
		 $this->redirect("index.php","<p>Voyage booked successfully!</p>");
		
	}
	
	public function redirect($url, $message = '', $wait = 5) {
		echo $message . "<p>You will be redirected to your destination in <span id='counter'>" . $wait . "</span> second(s). <a href='" . $url . "'>Click Here</a> to go there now.</p>" .
		'<script type="text/javascript">
function countdown() {
    var i = document.getElementById("counter");
    if (parseInt(i.innerHTML)<=0) {
        location.href = "' . $url . '";
    }
	else {
    	i.innerHTML = parseInt(i.innerHTML)-1;
	}
}
setInterval(function(){ countdown(); },1000);
</script>';
	}

}

?>