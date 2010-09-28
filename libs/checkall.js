function checkAll(theForm, cName, status) {
 for (i=0,n=theForm.elements.length;i<n;i++)
  if (theForm.elements[i].className.indexOf(cName) !=-1) {
   theForm.elements[i].checked = status;
  }
}


function setBox(checkname) {
 checkname.disabled = false;
}

function unsetBox(checkname) {
 checkname.disabled = true;
}

function showElement(element) {
 document.getElementById(element).style.display = 'block';
}

function hideElement(element) {
 document.getElementById(element).style.display = 'none';
}

