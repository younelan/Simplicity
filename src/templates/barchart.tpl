<div id="{{GRAPH_ID}}" style="width: 100%; height: 300px;"></div>
<div class="data-list">
    {{DATA_LIST}}
</div>

<script>
// Bar chart using D3.js for {{GRAPH_ID}}
(function() {
    var data = [
        {{DATASET}}
    ];
    
    if (data.length === 0) return;
    
    var margin = {top: 20, right: 30, bottom: 40, left: 50},
        width = 300 - margin.left - margin.right,
        height = 200 - margin.top - margin.bottom;
    
    var svg = d3.select("#{{GRAPH_ID}}")
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
    
    var x = d3.scaleBand()
        .range([0, width])
        .domain(data.map(function(d) { return d.label; }))
        .padding(0.1);
    
    var y = d3.scaleLinear()
        .domain([0, d3.max(data, function(d) { return d.value; })])
        .range([height, 0]);
    
    svg.selectAll(".bar")
        .data(data)
        .enter().append("rect")
        .attr("class", "bar")
        .attr("x", function(d) { return x(d.label); })
        .attr("width", x.bandwidth())
        .attr("y", function(d) { return y(d.value); })
        .attr("height", function(d) { return height - y(d.value); })
        .attr("fill", function(d, i) { 
            var colors = ["#1f77b4", "#ff7f0e", "#2ca02c", "#d62728", "#9467bd", "#8c564b", "#e377c2", "#7f7f7f", "#bcbd22", "#17becf"];
            return colors[i % colors.length]; 
        });
    
    svg.append("g")
        .attr("transform", "translate(0," + height + ")")
        .call(d3.axisBottom(x))
        .selectAll("text")
        .style("text-anchor", "end")
        .attr("dx", "-.8em")
        .attr("dy", ".15em")
        .attr("transform", "rotate(-45)");
    
    svg.append("g")
        .call(d3.axisLeft(y));
})();
</script>
