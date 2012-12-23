<? 
    include('class.rid.php'); 
    $stats = new RID(file_get_contents('RID.CAT'));
    $stats->analyze(file_get_contents('friends-pilot-full.txt'));
    $data = $stats->retrieve_data(array('PRIMARY'));
?>
<html>
  <head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">

      // Load the Visualization API and the piechart package.
      google.load('visualization', '1.0', {'packages':['corechart']});

      // Set a callback to run when the Google Visualization API is loaded.
      google.setOnLoadCallback(drawChart);

      // Callback that creates and populates a data table,
      // instantiates the pie chart, passes in the data and
      // draws it.
      function drawChart() {

        // Create the data table.
        var data = new google.visualization.DataTable();
        
        <?php $stats->make_data( 'data', $data ); ?>

        // Set chart options
        var options = {'title':'Emotional Linguistics in the first Friends Episode',
                       'width':1000,
                       'height':600};

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
  </head>

  <body>
    <!--Div that will hold the pie chart-->
    <div id="chart_div" style="width:1000px; margin:0 auto;"></div>
  </body>
</html>