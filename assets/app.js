/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import 'bootstrap/dist/css/bootstrap.css';
import './styles/app.css';
import 'js-datepicker/src/datepicker.scss';

import 'bootstrap/dist/js/bootstrap';

// datepicker
import datepicker from 'js-datepicker';

Array.from(document.getElementsByClassName('js-datepicker')).forEach(el => {
  datepicker(el, {
    formatter: (input, date, instance) => {
      const month = ('0' + (date.getMonth() + 1)).slice(-2);
      const day = ('0' + date.getDate()).slice(-2);
      input.value = `${date.getFullYear()}-${month}-${day}`;
    }
  });
})
