<?php

// Show the Density/Temperature chart
// GET Parameters:
// hours = number of hours before now() to be displayed
// days = hours x 24   
// weeks = days x 7    
// name = iSpindle name
 
// DB config values will be pulled from differtent location and user can personalize this file: common_db_config.php
// If file does not exist, values will be pulled from default file
 
if ((include_once './config/common_db_config.php') == FALSE){
       include_once("./config/common_db_default.php");
      }
     include_once("include/common_db_query.php");

// Check GET parameters (for now: Spindle name and Timeframe to display) 
if(!isset($_GET['hours'])) $_GET['hours'] = 0; else $_GET['hours'] = $_GET['hours'];
if(!isset($_GET['name'])) $_GET['name'] = 'iSpindel001'; else $_GET['name'] = $_GET['name'];
if(!isset($_GET['reset'])) $_GET['reset'] = defaultReset; else $_GET['reset'] = $_GET['reset'];
if(!isset($_GET['days'])) $_GET['days'] = 0; else $_GET['days'] = $_GET['days'];    
if(!isset($_GET['weeks'])) $_GET['weeks'] = 0; else $_GET['weeks'] = $_GET['weeks'];
                                                                            
// Calculate Timeframe in Hours                                             
$timeFrame = $_GET['hours'] + ($_GET['days'] * 24) + ($_GET['weeks'] * 168);
if($timeFrame == 0) $timeFrame = defaultTimePeriod;
$tftemp = $timeFrame;           
$tfweeks = floor($tftemp / 168);
$tftemp -= $tfweeks * 168;    
$tfdays = floor($tftemp / 24);
$tftemp -= $tfdays * 24;
$tfhours = $tftemp;                                
                                                   
list($valrange,$valdiff,$valTemperature_avg,$Min_temp_val,$Max_temp_val,$Min_diff_val,$Max_diff_val) = getChartValuesperdayrpi($conn, $_GET['name'], $timeFrame, $_GET['reset']);
list($RecipeName, $show) = getCurrentRecipeName($conn, $_GET['name'], $timeFrame, $_GET['reset']);

// Get fields from database in language selected in settings
$file = "plato4";
$recipe_name = get_field_from_sql($conn,'diagram',"recipe_name");
$first_y = get_field_from_sql($conn,$file,"first_y");
$second_y = get_field_from_sql($conn,$file,"second_y");
$x_axis = get_field_from_sql($conn,$file,"x_axis");
$subheader = get_field_from_sql($conn,$file,"timetext");
$subheader_reset = get_field_from_sql($conn,$file,"timetext_reset");
$subheader_weeks = get_field_from_sql($conn,'diagram',"timetext_weeks");
$subheader_days = get_field_from_sql($conn,'diagram',"timetext_days");
$subheader_hours = get_field_from_sql($conn,'diagram',"timetext_hours");
$header_no_data_1 = get_field_from_sql($conn,'diagram',"header_no_data_1");
$header_no_data_2 = get_field_from_sql($conn,'diagram',"header_no_data_2");
$header_no_data_3 = get_field_from_sql($conn,'diagram',"header_no_data_3");
$not_calibrated = get_field_from_sql($conn,'diagram',"not_calibrated"); 
$tooltip_at = get_field_from_sql($conn,'diagram',"tooltip_at");
$tooltip_time = get_field_from_sql($conn,'diagram',"tooltip_time");

// define header displayed in diagram depending on value for recipe
if ($RecipeName <> '') {
    $Header=$_GET['name'].' | ' . $recipe_name .' ' . $RecipeName;
    }
else {
    $Header='iSpindel: ' . $_GET['name'];
    }


// Header will show, that there is no data available, and displays timeframe user needs to go back to see data in diagram
if (!$_GET['reset'])
{
 $DataAvailable=isDataAvailable($conn, $_GET['name'], $timeFrame);
  if($DataAvailable[0]=='0')
  {
   $Header='iSpindel: ' . $header_no_data_1 . ' ' . $_GET['name']. ' ' . $header_no_data_2 . ' ' .$DataAvailable[1]. ' ' . $header_no_data_3;
  }
}

// define subheader to be displayed in diagram
$timetext = $subheader . ' ';
if($_GET['reset']) {
    $timetext = $subheader_reset . ' ';
    }
if($tfweeks != 0) {
    $timetext .= $tfweeks . ' ' . $subheader_weeks;
    }
if($tfdays != 0) {
    $timetext .= $tfdays . ' ' . $subheader_days;
    }
$timetext .= $tfhours . ' ' . $subheader_hours;

?>

<!DOCTYPE html>
<html>
<head>
  <title>iSpindle Data</title>
  <meta http-equiv="refresh" content="120">
  <meta name="Keywords" content="iSpindle, iSpindel, Chart, genericTCP">
  <meta name="Description" content="iSpindle Fermentation Chart">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
  <script src="http://code.highcharts.com/highcharts.js"></script>
  <script src="include/moment-timezone-with-data.js"></script>

<script type="text/javascript">

// define constants for data in chart. Allows for mor than two variables. Recipe information is included here and can be displayed in tooltip
const chartRange=[<?php echo $valrange;?>]
const chartTemp=[<?php echo $valTemperature_avg;?>]
const chartDiff=[<?php echo $valdiff;?>]
const Max_chart_val_temp =<?php echo  $Max_temp_val;?>

const Min_chart_val_temp =<?php echo  $Min_temp_val;?>

const Max_chart_val_diff =<?php echo  $Max_diff_val;?>

const Min_chart_val_diff =<?php echo  $Min_diff_val;?>

// define constants to be displayed in diagram -> no php code needed in chart
const recipe_name=[<?php echo "'".$recipe_name."'";?>]
const first_y=[<?php echo "'".$first_y."'";?>]
const second_y=[<?php echo "'".$second_y."'";?>]
const x_axis=[<?php echo "'".$x_axis."'";?>]
const chart_header=[<?php echo "'" . $Header . "'";?>]
const chart_subheader=[<?php echo "'" . $timetext . "'";?>]
const tooltip_at=[<?php echo "'".$tooltip_at."'";?>]
const tooltip_time=[<?php echo "'".$tooltip_time."'";?>]

$(function () 
{
  var chart;
 
  $(document).ready(function() 
  {
                    
    if ('<?php echo $isCalib;?>' == '0')
    {
        document.write('<h2>iSpindel \'<?php echo $_GET['name'] . ' ' . $not_calibrated;?>\'</h2>');
    }
    else
    {
        Highcharts.setOptions({
              global: {
                  timezone: 'Europe/Berlin'
              }
          });
                
        chart = new Highcharts.Chart(
        {
            chart:
            {
                renderTo: 'container'
            },
            title:
            {
                text: chart_header
            },
            subtitle:
            { 
                      text: chart_subheader                 
            },                                                                
            xAxis:
            {
                type: 'datetime',
                gridLineWidth: 1,
                title:
            {
                text: x_axis
            }
            },
            yAxis: [
                {
                    startOnTick: false,
                    endOnTick: false,
                    min: 0,
                    max: Max_chart_val_diff,
                    title:
                    {
                        text: first_y
                    },
                    labels:
                    {
                        align: 'left',
                        x: 3,
                        y: 16,
                        formatter: function()
                        {
                            return this.value + '°P'
                        }
                    },
                    showFirstLabel: false
                    },{
                    // linkedTo: 0,
                    startOnTick: true,
                    endOnTick: true,
                    min: Min_chart_val_temp,
                    max: Max_chart_val_temp,
                    gridLineWidth: 0,
                    opposite: true,
                    title: {
                        text: second_y
                    },
                    labels: {
                        align: 'right',
                        x: -3,
                        y: 16,
                        formatter: function() 
                        {
                            return this.value +'°C'
                        }
                    },
                    showFirstLabel: false
                }
            ],
            tooltip: {
       		 crosshairs: [true,true],
        	shared: true	
    		},  
            legend: 
            {
                enabled: true
            },
            credits:
            {
                enabled: false
            },
            series:
            [{
            	name: 'Temperature',
            	yAxis: 1,
        			data: chartTemp,
        			zIndex: 1,
        			tooltip: 
        			{
        				valueSuffix: " °C"
        			},
        			marker: {
            		fillColor: 'white',
            		lineWidth: 2,
            		lineColor: Highcharts.getOptions().colors[0]
        		}
        		}
        		, 
				{
            	name: 'Diff Plato',
            	yAxis: 0,
            	data: chartDiff,
            	tooltip: 
        			{
        				valueSuffix: " Δ°P"
        			},
        			marker: {
            		fillColor: 'white',
            		lineWidth: 2,
            		lineColor: Highcharts.getOptions().colors[1]
        		}
        		},
                {
        		name: 'Range',
        		yAxis: 1,
       			data: chartRange,
        		type: 'arearange',
        		lineWidth: 0,
        		tooltip: 
        			{
        				valueSuffix: " °C"
        			},
        		linkedTo: ':previous',
		        color: Highcharts.getOptions().colors[0],
        		fillOpacity: 0.3,
        		zIndex: 0,
        		marker: {
            	enabled: false
        	}
        	}
        	
            ] //series      
            });
    }
  });
});
</script>
</head>
<body>

<a href=/iSpindle/index.php><img src=./include/icons8-home-26.png></a>
 
<div id="wrapper">
 <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-more.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
  <div id="container" style="width:98%; height:98%; position:absolute"></div>
</div>
 
</body>
</html>