<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */


namespace Goteo\Model;

use Goteo\Application\Exception\ModelNotFoundException;
use Goteo\Application\Lang;
use Goteo\Application\Config;



/*
* Model for sphere
*/
class Sphere extends \Goteo\Core\Model {
    use Traits\SdgRelationsTrait;

    public
        $id,
        $name,
        $icon,
        $landing_match = false,
        $order = 1
        ;

    public static function getLangFields() {
        return ['name'];
    }

    /**
     * Get data about a sphere
     *
     * @param   int    $id         sphere id.
     * @return  Sphere object
     */
    static public function get($id, $lang = null) {

        if(!$lang) $lang = Lang::current();
        list($fields, $joins) = self::getLangsSQLJoins($lang, Config::get('sql_lang'));

        $sql="SELECT
                    sphere.id as id,
                    sphere.icon as icon,
                    sphere.order as `order`,
                    sphere.landing_match as landing_match,
                    $fields
              FROM sphere
              $joins
              WHERE sphere.id = :id";
        $query = static::query($sql, [':id' => $id]);
        $item = $query->fetchObject(__CLASS__);

        if($item) {
            // TODO: to remove this? use getIcon instead
            $item->image = $item->getIcon();
            return $item;
        }

        throw new ModelNotFoundException("Sphere not found for ID [$id]");
    }

    public function getIcon() {
        if(!$this->iconImage instanceOf Image) {
            $this->iconImage = Image::get($this->icon ?: "sphere/{$this->id}.png");
            if(!$this->icon) $this->iconImage->setAsset(true);
        }
        return $this->iconImage;
    }

    public function setIcon($icon) {
        $this->icon = $icon instanceOf Image ? $icon->id : $icon;
        $this->iconImage = null;
        return $this;
    }

    // For compatibility
    public function getImage() {
        return $this->getIcon();
    }

    /**
     * Sphere list
     *
     * @param  array  $filters
     * @return mixed            Array of spheres
     */
    public static function getAll($filters = array(), $lang=null) {

        $values = [];
        $filter = [];

        $list = [];

        if($filters['landing_match']) {
            $filter[] = "sphere.landing_match=1";
        }

        if($filter) {
            $sql = " WHERE " . implode(' AND ', $filter);
        }


        if(!$lang) $lang = Lang::current();
        $values['viewLang'] = $lang;
        list($fields, $joins) = self::getLangsSQLJoins($lang, Config::get('sql_lang'));

        $sql = "SELECT  sphere.id as id,
                        sphere.icon as icon,
                        sphere.order as `order`,
                        sphere.landing_match as landing_match,
                        $fields
                FROM sphere
                $joins
                $sql
                ORDER BY sphere.order ASC, sphere.name ASC";


        $query = self::query($sql, $values);
        //print(\sqldbg($sql, $values)); die();

        if($query = self::query($sql, $values)) {
            return $query->fetchAll(\PDO::FETCH_CLASS, __CLASS__);
        }

        return [];

    }

    /**
     * Save.
     *
     * @param   type array  $errors
     * @return  type bool   true|false
     */
    public function save(&$errors = array()) {

        if (!$this->validate($errors))
            return false;

        $fields = ['name', 'icon', 'landing_match', 'order'];

        try {
            $this->dbInsertUpdate($fields);

            return true;
        } catch(\PDOException $e) {
            $errors[] = "Sphere save error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Validate.
     *
     * @param   type array  $errors     Errores devueltos pasados por referencia.
     * @return  type bool   true|false
     */
    public function validate(&$errors = array()) {
        if(empty($this->name)) {
            $errors[] = "Emtpy name";
        }
        return true;
    }


}


