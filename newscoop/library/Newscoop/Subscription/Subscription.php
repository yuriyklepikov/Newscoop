<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Subscription;

use Newscoop\Entity\Publication,
    Newscoop\Entity\User;

/**
 * Subscription entity
 * @Entity(repositoryClass="Newscoop\Subscription\SubscriptionRepository")
 * @Table(name="Subscriptions")
 */
class Subscription
{
    const TYPE_PAID = 'P';
    const TYPE_PAID_NOW = 'PN';
    const TYPE_TRIAL = 'T';

    /**
     * @Id @GeneratedValue
     * @Column(type="integer", name="Id")
     * @var int
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Newscoop\Entity\User")
     * @JoinColumn(name="IdUser", referencedColumnName="Id")
     * @var Newscoop\Entity\User
     */
    private $user;

    /**
     * @ManyToOne(targetEntity="Newscoop\Entity\Publication")
     * @JoinColumn(name="IdPublication", referencedColumnName="Id")
     * @var Newscoop\Entity\Publication
     */
    private $publication;

    /**
     * @Column(type="decimal", name="ToPay")
     * @var float
     */
    private $toPay = 0.0;

    /**
     * @Column(name="Type")
     * @var string
     */
    private $type;

    /**
     * @Column(name="Currency")
     * @var string
     */
    private $currency;

    /**
     * @Column(name="Active")
     * @var string
     */
    private $active;

    /**
     * @OneToMany(targetEntity="Newscoop\Subscription\Section", mappedBy="subscription", cascade={"persist", "remove"})
     * @var array
     */
    private $sections;

    /**
     */
    public function __construct()
    {
        $this->sections = new \Doctrine\Common\Collections\ArrayCollection();
        $this->currency = '';
        $this->active = false;
        $this->type = self::TYPE_PAID;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * Set user
     *
     * @param Newscoop\Entity\User $user
     * @return void
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return Newscoop\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set publication
     *
     * @param Newscoop\Entity\Publication $publication
     * @return Newscoop\Entity\Subscription
     */
    public function setPublication(Publication $publication)
    {
        $this->publication = $publication;
        return $this;
    }

    /**
     * Get publication
     *
     * @return Newscoop\Entity\Publication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * Get publication name
     *
     * @return string
     */
    public function getPublicationName()
    {
        return $this->publication->getName();
    }

    /**
     * Get publication id
     *
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publication->getId();
    }

    /**
     * Set to pay
     *
     * @param float $toPay
     * @return Newscoop\Entity\Subscription
     */
    public function setToPay($toPay)
    {
        $this->toPay = (float) $toPay;
        return $this;
    }

    /**
     * Get to pay
     *
     * @return float
     */
    public function getToPay()
    {
        return (float) $this->toPay;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Newscoop\Entity\Subscription
     */
    public function setType($type)
    {
        $this->type = strtoupper($type) === self::TYPE_TRIAL ? self::TYPE_TRIAL : self::TYPE_PAID;
        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Test if is trial
     *
     * @return bool
     */
    public function isTrial()
    {
        return $this->type === self::TYPE_TRIAL;
    }

    /**
     * Set active
     *
     * @param bool $active
     * @return Newscoop\Entity\Subscription
     */
    public function setActive($active)
    {
        $this->active = (bool) $active ? 'Y' : 'N';
        return $this;
    }

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive()
    {
        return strtoupper($this->active) === 'Y';
    }

    /**
     * Add sections
     *
     * @param array $values
     * @param Newscoop\Entity\Publication $publication
     * @return void
     */
    public function addSections(array $values, \Newscoop\Entity\Publication $publication)
    {
        $languages = array();
        if (!empty($values['individual_languages']) && $values['individual_languages']) {
            $languages = $values['languages'];
            if (empty($languages)) {
                throw new \InvalidArgumentException("No languages set for individual languages");
            }
        }

        foreach ($publication->getIssues() as $issue) {
            if (!empty($languages) && !in_array($issue->getLanguageId(), $languages)) {
                continue;
            }

            foreach ($issue->getSections() as $section) {
                if ($this->hasSection($section, $languages)) {
                    continue;
                }

                $subSection = new Section($this, $section);
                $subSection->setStartDate(new \DateTime($values['start_date']));
                $subSection->setDays($values['days']);

                if ($this->isTrial() || $values['type'] === self::TYPE_PAID_NOW) {
                    $subSection->setPaidDays($values['days']);
                }

                if (!empty($languages)) {
                    $subSection->setLanguage($issue->getLanguage());
                }
            }
        }
    }

    /**
     * Add section
     *
     * @param Newscoop\Subscription\Section $section
     * @return void
     */
    public function addSection(Section $section)
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
        }
    }

    /**
     * Test if has given section
     *
     * @param Newscoop\Subscription\Section $section
     * @param array $languages
     * @return bool
     */
    private function hasSection(\Newscoop\Entity\Section $section, array $languages)
    {
        foreach ($this->sections as $s) {
            if ($s->getSectionNumber() == $section->getNumber()) {
                if (!$s->hasLanguage()) {
                    return true;
                } else if (empty($languages)) {
                    $s->setLanguage(null);
                    return true;
                } else if ($s->getLanguage() == $section->getLanguage()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get sections
     *
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }
}