<div style='overflow:auto;min-height:250px;width:600px;background-color:#FFC;display:inline-block;position:relative;'>
    <canvas id="{{GRAPH_ID}}" width="150" height="150" style='position:absolute;left:0px;float:left;'></canvas>
<div style='font-size:0.8em;position:absolute;left:160px;'>
{{DATA_LIST}}
</div>
</div>
    <script>
      (function() {
        'use strict';
        var dataset = [
{{DATASET}}
        ];
        
        var canvas = document.getElementById('{{GRAPH_ID}}');
        var ctx = canvas.getContext('2d');
        var centerX = canvas.width / 2;
        var centerY = canvas.height / 2;
        var radius = 70;
        
        var colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
        
        var total = dataset.reduce(function(sum, item) { return sum + item.count; }, 0);
        var currentAngle = -Math.PI / 2; // Start at top
        
        dataset.forEach(function(item, index) {
          var sliceAngle = (item.count / total) * 2 * Math.PI;
          
          ctx.beginPath();
          ctx.moveTo(centerX, centerY);
          ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
          ctx.closePath();
          ctx.fillStyle = colors[index % colors.length];
          ctx.fill();
          ctx.strokeStyle = '#fff';
          ctx.lineWidth = 2;
          ctx.stroke();
          
          currentAngle += sliceAngle;
        });
      })();
    </script>