<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="graph-container">
                <div class="graph-content">
                    <div id="{{GRAPH_ID}}" class="graph-canvas vbar-canvas"></div>
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
    
    var container = document.getElementById('{{GRAPH_ID}}');
    var colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
    var maxValue = Math.max.apply(Math, dataset.map(function(d) { return d.value; }));
    
    dataset.forEach(function(item, index) {
      var barContainer = document.createElement('div');
      barContainer.className = 'vbar-container';
      
      var bar = document.createElement('div');
      bar.className = 'vbar';
      var height = Math.max(10, (item.value / (maxValue)) * 140);
      bar.style.height = height + 'px';
      bar.style.backgroundColor = colors[index % colors.length];
      bar.title = item.label + ': ' + item.value;
      
      barContainer.appendChild(bar);
      container.appendChild(barContainer);
    });
  })();
</script>
