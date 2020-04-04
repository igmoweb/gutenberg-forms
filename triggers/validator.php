<?php

    class Validator {

        public function isEmpty($string) {

            $len = strlen(trim($string));

            if ( $len === 0 ) return true;

            return false;

        }

        public function sanitizedValue( $type, $value ) {
            # This function will sanitize all the fields according to thier types with php build in filters.

            if ($type === "email") {
                return filter_var( $value, FILTER_SANITIZE_EMAIL );
            }

            return filter_var( $value, FILTER_SANITIZE_SPECIAL_CHARS );

        }

		/**
		 * @param $emails_string
		 * @return boolean
		 */

        public function is_valid_emails( $emails_string ) {

        	$emails = explode(",", $emails_string);

        	foreach ($emails as $key => $email) {

        		if ( $this->isEmail( $email ) === false ) {
        			// this means one of the email provided is invalid so we had to break out of the loop

					return false;
					break;

				} else continue;

			}

			return true;
		}

		/**
		 * @param $email {string}
		 * This function test if the given $email by the user is multiple emails => ex: "test@email.com,test@gmail.com"
		 * @return array
		 */

        public function is_multiple_email($email) {
        	// for enabling user to send email to more then one email

			$exploaded = explode( ',' , $email ); // converting in array;
			$mailsLen = count($exploaded);


			if ($mailsLen === 1) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * @param $email {string}
		 * This function test if the email is invalid
		 * @return bool
		 */

		public function is_valid_admin_mail($email) {


			if ($this->is_multiple_email($email)) {

				return $this->is_valid_emails($email);

			} else {

				if ( !filter_var( $email , FILTER_VALIDATE_EMAIL )  ) {
					return false;
				}

				return true;
			}

		}

        public function isEmail($email) {
			if ( !filter_var( $email , FILTER_VALIDATE_EMAIL )  ) {
				return false;
			}

			return true;
        }

		/**
		 * @param $website {string}
		 * This function test if the url is invalid
		 * @return bool
		 */

        public function isURL( $website ) {
            if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$website)) {
                return false;
            } else {
                return true;
            }
        }


        public function decode($t) {

            $dV = explode('-', urldecode(base64_decode($t)));

            if (count($dV) === 1) {
                return array();
            }

            $extra_meta_exists = array_key_exists( 7, $dV ); // testing if the extra meta exist
            $extra_meta = $extra_meta_exists ? preg_replace('/[0-9]+/' , '', $dV[7]) : NULL;


            return array(
                'is_required' => $dV[4],
                'type'        => preg_replace('/[0-9]+/' , '' , $dV[2]),
                'admin_id'    => $dV[6],
                'extra_meta' => $extra_meta,
                'field_id'    => $dV[3],
            );



        }

        public function isNumber($num) {

            $as_integer = (int)$num;

            if (gettype($as_integer) === "integer") return true;
            return false;

        }

        public function is_file_empty($file) {

            if (!is_null($file) && $file['name'] === '' && $file['type'] === "" && $file['tmp_name'] === '') {
                return true;
            } else {
                return false;
            }

        }

        public function is_checkbox_empty($arr) {

            if (count($arr) === 0) {
                return true;
            } else {
                return false;
            }

        }

        public function test_file_formats($ext, $allowed) {
            $allowed_defaults =  array(
                "jpg",
                "jpeg",
                "png",
                "gif",
                "pdf",
                "doc",
                "docx",
                "ppt",
                "pptx",
                "odt",
                "avi",
                "ogg",
                "m4a",
                "mov",
                "mp3",
                "mp4",
                "mpg",
                "wav",
                "wmv"
            );

            if (!is_null($allowed) && sizeof($allowed) !== 0) {
                return in_array($ext,$allowed);

            } else {
                return in_array( $ext,$allowed_defaults );
            }

        }

        public function validate( $type, $value, $decode_id ) {

            $decoded_field = $this->decode($decode_id);

            if ( count($decoded_field) === 0) {
                return false;
            }  else if (count($decoded_field) !== 0)  {

                $type = $decoded_field['type'];
                $required = $decoded_field['is_required'];

                if ($type === "email" and $required === 'true') {
                    return $this->isEmpty($value) ? false : $this->isEmail($value);
                } else if ($type === "email" and $required === 'false') {
                    return $this->isEmpty($value) ? true : $this->isEmail($value);
                } else if ($type === 'website' and $required === 'true') {
                    return $this->isEmpty($value) ? false : $this->isURL($value);
                } else if ($type === 'website' and $required === 'false') {
                    return  $this->isEmpty($value) ? true :  $this->isURL($value);
                } else if ($type === 'number' and $required === 'true') {
                    return $this->isEmpty($value) ? true : $this->isNumber($value);
                } else if ($type === 'number' and $required === 'false') {
                    return $this->isEmpty($value) ? true :  $this->isNumber($value);
                } else if ($type === 'file_upload' and $required === 'true') {
                    return $this->is_file_empty($value) ? false : true;
                } else if ($type === 'file_upload' and $required === 'false') {
                    return $this->is_file_empty($value) ? true : true;
                } else if ($type === 'checkbox' and $required === 'true') {
                    return $this->is_checkbox_empty($value) ? false : true;
                } else if ($type === 'checkbox' and $required === 'false') {
                    return $this->is_checkbox_empty($value) ? true : true;
                } else if ($required === 'true') {
                    return $this->isEmpty($value) ? false : true;
                } else if ($required === 'true') {
                    return $this->isEmpty($value) ? true : false;
                } else {
                    return true;
                }
            }

        }

    }


