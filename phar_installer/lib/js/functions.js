function add_message(str) {
  var theDiv = document.getElementById("inner"),
   newNode = document.createElement("p");
  newNode.className = "message blue";
  newNode.innerHTML = str;
  theDiv.appendChild(newNode);
  theDiv.scrollTop = theDiv.scrollHeight;
}

function add_verbose(str) {
  var theDiv = document.getElementById("inner"),
   newNode = document.createElement("p");
  newNode.className = "verbose";
  newNode.innerHTML = str;
  theDiv.appendChild(newNode);
  theDiv.scrollTop = theDiv.scrollHeight;
}

function add_error(str) {
  var theDiv = document.getElementById("inner"),
   newNode = document.createElement("p");
  newNode.innerHTML = str;
  newNode.className = "message red";
  theDiv.appendChild(newNode);
  theDiv.scrollTop = theDiv.scrollHeight;
}

function set_block_html(id, html) {
  var theDiv = document.getElementById(id);
  theDiv.innerHTML = html;
}

function alldone(html) {
  var listRoot = document.getElementById("installer-indicator"),
   theNode = listRoot.getElementsByClassName("current-step")[0],
   theDiv = document.getElementById("bottom_nav"),
   thePlace = document.getElementById("complete");
  thePlace.innerHTML = html;
  theDiv.style.display = "block";
  if (theNode) {
    theNode.classList.remove("current-step");
    theNode.classList.add("done-step");
  }
}

function finish() {
  var theDiv = document.getElementById("bottom_nav");
  theDiv.style.display = "block";
}

window.onload = function() {
  var freshen = document.getElementById("freshen"),
    upgrade = document.getElementById("upgrade");

  if (upgrade) {
    upgrade.onclick = function() {
      return confirm(cmsms_lang.upgrade);
    };
  } else if (freshen) {
    freshen.onclick = function() {
      return confirm(cmsms_lang.freshen);
    };
  }
};
