 

  function whisp_delete(id) {
    if (confirm('Do you really want to delete?')) {
        location.href = window.location.href + '&whisp_delete=' + id;
    } else {
 
    }
}

 function whispwpCodeFunction() {
   var copyText = document.getElementById("whispCopyCodeInput");
   copyText.select();
   document.execCommand("Copy");
   alert("Copy : " + copyText.value);
 }
 