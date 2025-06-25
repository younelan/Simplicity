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
        <!-- Repeat col-md-4 for additional graphs -->
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
    var radius = Math.min(canvas.width, canvas.height) / 2 - 10; // Adjust radius for padding
    
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

    // Update graph keys with rectangles
    var graphDataDiv = document.querySelector(`#{{GRAPH_ID}}`).closest('.graph-container').querySelector('.graph-data');
    graphDataDiv.innerHTML = dataset.map((item, index) => `
      <div class="graph-key">
        <div class="graph-key-color" style="background-color: ${colors[index % colors.length]};"></div>
        <span>${item.label}: ${item.count}</span>
      </div>
    `).join('');
  })();
</script>
<style>
.graph-data {
    background-color: #20365c;
    border-radius: 10px;
    padding: 5px; /* Adjusted padding to reduce space above and below */
    height: auto; /* Allow height to adjust based on content */
    min-height:50%;
    overflow: hidden; /* Prevent text from overflowing outside the container */
}
.graph-canvas {
    background-color: #e2e2b7;
    border-radius: 10px;
    margin-bottom: 5px;
}
.graph-title {
    background-color: #000;
    color: white;
    border-radius: 3px;
    padding: 5px;
    margin-top: 0;
    margin-bottom: 5px;
}
.graph-key {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
    color: #fff9e3;
    text-shadow: 2px 1px #637286;
    font-size: 0.8em;
}
.graph-key-color {
    width: 10px;
    height: 10px;
    display: inline-block;
    margin-right: 5px;
    flex-shrink: 0; /* Prevent the rectangle from resizing */
}
</style>