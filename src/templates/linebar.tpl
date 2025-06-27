<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="graph-container">
                <div class="graph-content">
                    <div id="{{GRAPH_ID}}" class="graph-canvas linebar-canvas"></div>
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
    var yLabels = {{Y_VALUES}};
    
    var container = document.getElementById('{{GRAPH_ID}}');
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', '0 0 280 150');
    container.appendChild(svg);
    
    var margin = {top: 20, right: 20, bottom: 40, left: 50};
    var width = 280 - margin.left - margin.right;
    var height = 150 - margin.top - margin.bottom;
    
    // Sort data by x value
    dataset.sort(function(a, b) {
      return a.x.localeCompare(b.x);
    });
    
    var xValues = dataset.map(function(d) { return d.x; });
    var yValues = dataset.map(function(d) { return d.y; });
    var maxY = Math.max.apply(Math, yValues);
    var minY = Math.min.apply(Math, yValues);
    
    // Create scales
    var xScale = function(i) { return margin.left + (i / (xValues.length - 1)) * width; };
    var yScale = function(y) { return margin.top + height - ((y - minY) / (maxY - minY || 1)) * height; };
    
    // Draw axes
    var axisGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    
    // X axis
    var xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    xAxis.setAttribute('x1', margin.left);
    xAxis.setAttribute('y1', margin.top + height);
    xAxis.setAttribute('x2', margin.left + width);
    xAxis.setAttribute('y2', margin.top + height);
    xAxis.setAttribute('stroke', '#333');
    axisGroup.appendChild(xAxis);
    
    // Y axis
    var yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    yAxis.setAttribute('x1', margin.left);
    yAxis.setAttribute('y1', margin.top);
    yAxis.setAttribute('x2', margin.left);
    yAxis.setAttribute('y2', margin.top + height);
    yAxis.setAttribute('stroke', '#333');
    axisGroup.appendChild(yAxis);
    
    svg.appendChild(axisGroup);
    
    // Draw bars
    dataset.forEach(function(d, i) {
      var barContainer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      var bar = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      var x = xScale(i);
      var y = yScale(d.y);
      var barHeight = height - yScale(d.y);
      
      bar.setAttribute('x', x - 10);
      bar.setAttribute('y', y);
      bar.setAttribute('width', 20);
      bar.setAttribute('height', barHeight);
      bar.setAttribute('fill', '#1f77b4');
      
      barContainer.appendChild(bar);
      svg.appendChild(barContainer);
    });
    
    // Add axis labels if provided
    var xlabel = '{{X_LABEL}}';
    var ylabel = '{{Y_LABEL}}';
    
    if (xlabel) {
      var xLabelText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      xLabelText.setAttribute('x', margin.left + width / 2);
      xLabelText.setAttribute('y', margin.top + height + 35);
      xLabelText.setAttribute('text-anchor', 'middle');
      xLabelText.setAttribute('font-size', '12');
      xLabelText.setAttribute('font-weight', 'bold');
      xLabelText.textContent = xlabel;
      svg.appendChild(xLabelText);
    }
    
    if (ylabel) {
      var yLabelText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      yLabelText.setAttribute('x', 15);
      yLabelText.setAttribute('y', margin.top + height / 2);
      yLabelText.setAttribute('text-anchor', 'middle');
      yLabelText.setAttribute('font-size', '12');
      yLabelText.setAttribute('font-weight', 'bold');
      yLabelText.setAttribute('transform', 'rotate(-90, 15, ' + (margin.top + height / 2) + ')');
      yLabelText.textContent = ylabel;
      svg.appendChild(yLabelText);
    }
  })();
</script>
