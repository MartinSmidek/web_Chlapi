// uživatelské funkce aplikace RR
/* global Ezer, Form, Var, Field, FieldList, Edit, Select, View, Label, Highcharts */ // pro práci s Netbeans
"use strict";
// ====================================================================================> highcharts
// --------------------------------------------------------------------------------- highcharts load
// zavede dynamicky potřebné moduly highcharts
function highcharts_load() {
  if (typeof Highcharts=="undefined") {
    let code= `../ezer${Ezer.version}/client/licensed/highcharts/code`;
    for (const modul of ['highcharts.js','highcharts-more.js',
        'modules/heatmap.js','modules/exporting.js','modules/export-data.js',
        'modules/accessibility.js']) {
      jQuery('head').append(`<script type="text/javascript" src="${code}/${modul}"></script>`);
    }
  }
}
// -------------------------------------------------------------------------------- highcharts clear
// vymaže chart
function highcharts_clear(container) {
  if (container==undefined) container= 'container';
  jQuery('#'+container).html('');
}
// --------------------------------------------------------------------------------- highcharts show
// par: series:[{name:str,list:str},...]
function highcharts_show(par,container) {
  // test zavedení modulu
  if (container==undefined) container= 'container';
  if (typeof Highcharts=="undefined") {
    jQuery('#'+container).html("není zaveden modul highcharts - použij &highcharts=1");
    return;
  }
  // rozeznání typu grafu
  let chart= {};
  switch (par['chart']) {
    case 'pie': { // --------------------------------------------------- pie
      chart= {
        chart: {type: 'pie'},
        title: {text: ''},
        plotOptions: {pie:{showInLegend:true, 
//            dataLabels:{enabled: false}, 
            dataLabels: {
              enabled: true,
              distance: -50,
              format: '{point.percentage:.1f} %',
            },
            startAngle: 90}},
        series: [{name:'Values', data:[]}],
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            y: 50,
            padding: 3,
            itemMarginTop: 5,
            itemMarginBottom: 5,
            itemStyle: { lineHeight: '14px' }
        }
      };
      // doplnění předanými daty
      for (const p in par) {
        switch (p) {
          case 'chart':
            chart.chart= {};
            chart.chart.type= par[p];
            break;
          case 'series': // series= [{name, data:[{name,y},...]}]
            for (let name_value of par[p][0].data) {
              name_value.y= Number(name_value.y);
              chart.series[0].data.push(name_value);
            }
            break;
          case 'data': // string čárkami oddělené name:hodnota
            let name, value;
            for (let name_value of par[p].split(',')) {
              [name, value]= name_value.split(':');
              chart.series[0].data.push({name:name,y:Number(value)});
            }
            break;
          case 'title':
          case 'subtitle':
            chart[p].text= par[p];
            break;
          default:
            chart[p]= par[p];
            break;
        }
      }
      break;
    }
    case 'heatmap': { // ----------------------------------------------- heatmap
      chart= {
        chart: {type: 'heatmap',marginTop: 40,marginBottom: 80,plotBorderWidth: 1},
        title: {text: ''},
        xAxis: {categories: []},
        yAxis: {categories: [],title: null},
        colorAxis: {min: 0,minColor: '#FFFFFF',maxColor: '#aaa'},
        legend: {align: 'right',layout: 'vertical',margin: 0,verticalAlign: 'top',y: 25,symbolHeight: 280},
        tooltip: {
          formatter: function () { if (chart.tooltip.data) {
            return chart.tooltip.data[this.point.x][this.point.y];
          }}
        },
        series: [{name: '',borderWidth: 1,data: [],
            dataLabels: {enabled: true,color: '#000000'}
        }],
      };
      // doplnění předanými daty
      chart.title.text= par['title_text'];
      chart.colorAxis.maxColor= par['colorAxis_maxColor'];
      chart.xAxis.categories= par['xAxis_categories'];
      chart.yAxis.categories= par['yAxis_categories'];
      chart.series[0].data= par['series_0_data'];
      if (par['tooltip_data']) {
        chart.tooltip.data= par['tooltip_data'];
      }
      break;
    }
    default:  {      // ----------------------------------------------- bar
      chart= {
        exporting: {showTable: false},    
        title: {text: ' '},
        subtitle: {text: ''},
        legend: {layout: 'vertical', align: 'right', verticalAlign: 'middle'},
        series: [],
        responsive: {
          rules: [{
            condition: {
              maxWidth: 500},
              chartOptions: 
                {legend: {layout: 'horizontal',align: 'center',verticalAlign: 'bottom'}
              }
          }]
        }
      };
      // parametrizace
      for (const p in par) {
        switch (p) {
          case 'chart':
            chart.chart= {};
            chart.chart.type= par[p];
            break;
          case 'series_done':
            chart.series= par[p];
            break;
          case 'series':
            for (const serie of par[p]) {
              if (serie.type=='line') {
                chart[p].push({name:serie.name, type:'line', data:serie.data});
              }
              else {
                let data= typeof(serie.data)=='string' ? serie.data.split(',').map(x=>Number(x)) : (
                  Array.isArray(serie.data) ? serie.data : []);
                let s= {name:serie.name, data:data};
//                let s= {name:serie.name, data:data.map(x=>Number(x))};
                if (serie.color) s.color= serie.color;
                if (serie.dashStyle) s.dashStyle= serie.dashStyle;
                if (serie.marker) s.marker= serie.marker;
                chart[p].push(s);
              }
            }
            break;
          case 'title':
          case 'subtitle':
            chart[p].text= par[p];
            break;
          default:
            chart[p]= par[p];
            break;
        }
      }
      break;
    }
  }
  // zobrazení
  jQuery('div.highcharts-data-table').remove();
  Highcharts.chart(container, chart);
}
