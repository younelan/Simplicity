<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="graph-container">
                <div class="graph-content">
                    <canvas id="{{GRAPH_ID}}" class="graph-canvas"></canvas>
                </div>
                <div class="graph-text">
                    <div class="graph-data">
                        {{DATA_LIST}}
                    </div>
                </div>
            </div>
        </div>
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
    var radius = Math.min(canvas.width, canvas.height) / 2 - 10;
    
    var colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
    
    var total = dataset.reduce(function(sum, item) { return sum + item.count; }, 0);
    var currentAngle = -Math.PI / 2;
    
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
