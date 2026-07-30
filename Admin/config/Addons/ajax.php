<?php
include('config.php');

if(isset($_GET['degreeType'])){
     
    $degreeType = $_GET['degreeType'];
    if($degreeType == 0) {
        
        $select = mysqli_query($con,"SELECT * FROM `stream_master`");
    } else {

        $select = mysqli_query($con,"SELECT * FROM `stream_master` WHERE `ST_TYPE`='". $degreeType."'");
    }
    $output = '';
    if(mysqli_num_rows($select)>0){

        $output .= '<option selected value="0"> All </option>';
        while($row=mysqli_fetch_assoc( $select)){
            $output .='<option value="' . $row['ST_ID'] . '">' . $row['ST_NAME'] . '</option>';
        }

    }
    else
    {
        $output = ' <option selected value="0">All</option>';
    }
    echo $output;
}
?>