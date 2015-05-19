<?php

class Cpsi_Form_Woolcommmerce extends Cpsi_Form_Abstract {

    protected function _constructFormFromDefinition(array $formFields) {
        ?>

        <form id="corvus-autosubmit" method="POST" action="<?php echo $this->getAction() ?>">
            <?php foreach ($formFields as $name => $field) : ?>
                <input type="hidden" name="<?php echo $name ?>" 
                       value="<?= $field['value'] ?>"/>
                   <?php endforeach; ?>

            <script type="text/javascript">
                document.forms['corvus-autosubmit'].submit();
            </script>
        </form>
        <?php
    }

}
