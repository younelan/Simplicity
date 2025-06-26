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
.vbar-canvas {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    padding: 10px;
    box-sizing: border-box;
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
.vbar-container {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    margin: 0 1px;
    flex: 1;
    max-width: 30px;
    min-height: 100%;
    padding-top: 15px;
}
.vbar {
    width: 18px;
    transition: all 0.3s ease;
    cursor: pointer;
    min-height: 5px;
}
.vbar:hover {
    opacity: 0.8;
    transform: scaleY(1.1);
}
</style>

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
