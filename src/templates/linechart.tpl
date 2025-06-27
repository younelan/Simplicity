<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="graph-container">
                <div class="graph-content">
                    <div id="{{GRAPH_ID}}" class="graph-canvas line-canvas"></div>
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
    
    if (dataset.length === 0) return;
    
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
    
    var yValues = dataset.map(function(d) { return d.y; });
    var maxY = Math.max.apply(Math, yValues);
    var minY = Math.min.apply(Math, yValues);
    
    // Create scales
    var xScale = function(i) { return margin.left + (i / (dataset.length - 1)) * width; };
    var yScale = function(y) { 
      if (maxY === minY) return margin.top + height / 2;
      return margin.top + height - ((y - minY) / (maxY - minY)) * height; 
    };
    
    // Draw axes
    var axisGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    
    // X axis
    var xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    xAxis.setAttribute('x1', margin.left);
    xAxis.setAttribute('y1', margin.top + height);
    xAxis.setAttribute('x2', margin.left + width);
    xAxis.setAttribute('y2', margin.top + height);
    xAxis.setAttribute('stroke', '#333');
    xAxis.setAttribute('stroke-width', '1');
    axisGroup.appendChild(xAxis);
    
    // Y axis
    var yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    yAxis.setAttribute('x1', margin.left);
    yAxis.setAttribute('y1', margin.top);
    yAxis.setAttribute('x2', margin.left);
    yAxis.setAttribute('y2', margin.top + height);
    yAxis.setAttribute('stroke', '#333');
    yAxis.setAttribute('stroke-width', '1');
    axisGroup.appendChild(yAxis);
    
    svg.appendChild(axisGroup);
    
    // Draw line if more than one point
    if (dataset.length > 1) {
      var pathData = 'M';
      dataset.forEach(function(d, i) {
        var x = xScale(i);
        var y = yScale(d.y);
        pathData += (i === 0 ? '' : 'L') + x + ',' + y;
      });
      
      var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', pathData);
      path.setAttribute('stroke', '#1f77b4');
      path.setAttribute('stroke-width', '2');
      path.setAttribute('fill', 'none');
      svg.appendChild(path);
    }
    
    // Draw points
    dataset.forEach(function(d, i) {
      var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('cx', xScale(i));
      circle.setAttribute('cy', yScale(d.y));
      circle.setAttribute('r', '3');
      circle.setAttribute('fill', '#1f77b4');
      circle.setAttribute('stroke', '#fff');
      circle.setAttribute('stroke-width', '1');
      
      // Create title element for tooltip
      var title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
      title.textContent = d.x + ': ' + d.y;
      circle.appendChild(title);
      
      svg.appendChild(circle);
    });
    
    // Add X axis labels
    dataset.forEach(function(d, i) {
      if (i % Math.max(1, Math.floor(dataset.length / 5)) === 0) { // Show every nth label
        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', xScale(i));
        text.setAttribute('y', margin.top + height + 15);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', '10');
        text.setAttribute('fill', '#666');
        text.textContent = d.x;
        svg.appendChild(text);
      }
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
<style>
.graph-data {
    background-color: #20365c;
    border-radius: 10px;
    padding: 5px;
    height: auto;
    min-height: 50%;
    overflow: hidden;
}
.graph-canvas {
    background-color: #e2e2b7;
    border-radius: 10px;
    margin-bottom: 5px;
    width: 100%;
    height: 150px;
}
.line-canvas {
    display: flex;
    align-items: center;
    justify-content: center;
}
.graph-title {
    background-color: #000;
    color: white;
    border-radius: 3px;
    padding: 5px;
    margin-top: 0;
    margin-bottom: 5px;
}
.graph-data-entry {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
    color: #fff9e3;
    text-shadow: 2px 1px #637286;
    font-size: 0.8em;
}
.graph-data-color {
    width: 10px;
    height: 10px;
    display: inline-block;
    margin-right: 5px;
    flex-shrink: 0;
}
.graph-data-label {
    font-weight: bold;
    color: #e1b698;
    text-shadow: 2px 1px black;
}
.graph-data-value {
    color: #aaa;
}
</style>
