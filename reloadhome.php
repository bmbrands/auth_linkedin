<?php
require_once('../../config.php');
?>

<HTML>
<HEAD>
<script type="text/javascript">
  function reloadParentAndClose() {
        // reload the opener or the parent window
      window.opener.location.href = '<?php echo $CFG->wwwroot; ?>';
      self.close();
    }

</script>
</HEAD>
<BODY onLoad="reloadParentAndClose();">
<input type=button value="Close" onClick="reloadParentAndClose();" />
</BODY>
</HTML>