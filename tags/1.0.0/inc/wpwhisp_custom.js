 

  function whisp_delete(id) {
    if (confirm('Do you really want to delete?')) {
        location.href = window.location.href + '&whisp_delete=' + id;
    } else {
 
    }
}

 function whispwpCodeFunction(x) {
   var copyText = document.getElementById("whispCopyCodeInput"+x);
   copyText.select();
   document.execCommand("Copy");
   alert("Copy : " + copyText.value);
 }
 