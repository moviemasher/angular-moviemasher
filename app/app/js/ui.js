function toggle_visibilityCapture(){

 document.getElementById('CapturePanel').classList.toggle('hidden');
 document.getElementById('CapturePanel').classList.toggle('visible')
}



function toggle_visibilityInspector(){

 document.getElementById('InspectorPanel').classList.toggle('hidden');
 document.getElementById('InspectorPanel').classList.toggle('visible')
}


function toggle_visibilityImport(){

 document.getElementById('ContentPanel').classList.toggle('hidden');
 document.getElementById('ContentPanel').classList.toggle('visible')
}


//Filters
var idx = 0;
var filters = ['grayscale', 'sepia', 'blur', 'brightness', 'contrast', 'hue-rotate',
               'hue-rotate2', 'hue-rotate3', 'saturate', 'invert', ''];

function changeFilter(e) {
  var el = e.target;
  el.className = '';
  var effect = filters[idx++ % filters.length]; // loop through filters.
  if (effect) {
    el.classList.add(effect);
  }
}

document.querySelector('video').addEventListener('click', changeFilter, false);