        <div class="container-fluid">
          <footer>
            <?php if (fAuthorization::checkLoggedIn()) { ?>
            <p><a href="login.php?action=log_out">Log Out</a></p>
            <?php } ?>
          </footer>
        </div>
<?php if ($this->get('show_bubbles')) { ?>
<script>
    $(function () {
      $("img[rel=popover-below]")
        .popover({
          placement: "below", delayIn: 1000
        })
    })
    $(function () {
      $("img[rel=popover-above]")
        .popover({
          placement: "above", delayIn: 1000
        })
    })
</script>
<?php } ?>
  </body>
</html>
