<?php

namespace App\Entity\User;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Filter\CustomSearchUserFilter;
use App\Common\Constants\UserConstants;
use App\Controller\Api\User\EditController;
use App\Controller\Api\User\ForgotPassController;
use App\Controller\Api\User\Linkcy\CreateCardController;
use App\Controller\Api\User\LoginController;
use App\Controller\Api\User\LoginEmail\PrepareEmailLoginController;
use App\Controller\Api\User\LoginSMS\PrepareSmsLoginController;
use App\Controller\Api\User\ReinitializePassController;
use App\Controller\Api\User\RenewPasswordController;
use App\Controller\Api\User\ResendCodeController;
use App\Controller\Api\User\SignUpController;
use App\Controller\Api\User\VerifyCodeController;
use App\Entity\Advice;
use App\Entity\Enum\Step;
use App\Entity\Subscription\Subscription;
use App\Entity\Trait\StatusTrait;
use App\Entity\Trait\TimestampTrait;
use App\Entity\Transaction\Exchange;
use App\Entity\Transaction\Transaction;
use App\Repository\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['part' => Particular::class, 'pro' => Professional::class, 'admin' => Admin::class])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            controller: SignUpController::class,
            openapiContext: [
                'summary' => 'Create a new user',
                'description' => 'Creates a new user',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'role' => [
                                        'type' => 'string',
                                        'description' => 'Role : particular / professional'
                                    ],
                                    'conditionAccepted' => [
                                        'type' => 'boolean',
                                        'description' => 'True if the user accepts the conditions of use',
                                    ],
                                    'marketingAccepted' => [
                                        'type' => 'boolean',
                                        'description' => 'True if the user accepts to receive marketing information',
                                    ],
                                    'code' => [
                                        'type' => 'string',
                                        'description' => 'The code of the user',
                                    ],
                                    'firstName' => [
                                        'type' => 'string',
                                        'description' => 'The first name of the user',
                                    ],
                                    'lastName' => [
                                        'type' => 'string',
                                        'description' => 'The last name of the user',
                                    ],
                                    'address' => [
                                        'type' => 'string',
                                        'description' => 'The address of the user',
                                    ],
                                    'postalCode' => [
                                        'type' => 'string',
                                        'description' => 'The postal code of the user',
                                    ],
                                    'city' => [
                                        'type' => 'string',
                                        'description' => 'The city of the user',
                                    ],
                                ],
                                'required' => ['firstName', 'lastName', 'code', 'address', 'postalCode', 'city'],
                            ],
                        ],
                    ],
                ],
            ]
        ),
        new Patch(
            uriTemplate: '/user/{id}',
            controller: EditController::class,
            denormalizationContext: ['groups' => ['user:write', 'user:pro:write', 'user:part:write']]
        ),
        new Patch(
            uriTemplate: '/user/new/pass',
            controller: RenewPasswordController::class,
            openapiContext: [
                'summary' => 'Renew user password',
                'description' => 'Renew the user password'
            ]
        ),
        new Post(
            uriTemplate: '/user/verify/{id}',
            controller: VerifyCodeController::class,
            openapiContext: [
                'summary' => 'Verify user code',
                'description' => 'Verify the user code confirmation on SMS/MAIL',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'integer', 'description' => 'The code of the user'],
                                    'type' => ['type' => 'string', 'description' => 'The type of confirmation : SMS/MAIL'],
                                    'for' => ['type' => 'string', 'description' => 'The confirmation is for : SIGN_UP_VER/SIGN_IN_VER'],
                                ],
                                'required' => ['code', 'type', 'for'],
                            ]
                        ],
                    ],
                ],
            ],
        ),
        new Post(
            uriTemplate: '/user/forgot',
            controller: ForgotPassController::class,
            openapiContext: [
                'summary' => 'Request password reset',
                'description' => 'Sends a password reset email to the user',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'url' => ['type' => 'string', 'description' => 'The url of the client',],
                                    'email' => ['type' => 'string', 'description' => 'The email of the user',],
                                ],
                                'required' => ['url', 'email'],
                            ],
                        ],
                    ],
                ],
            ]
        ),
        new Post(
            uriTemplate: '/user/reinitialize',
            controller: ReinitializePassController::class,
            openapiContext: [
                'summary' => 'Reset user password',
                'description' => 'Resets the user password using the provided token',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'token' => [
                                        'type' => 'string',
                                        'description' => 'The token of the user',
                                    ],
                                    'password' => [
                                        'type' => 'string',
                                        'description' => 'The password of the user',
                                    ],
                                ],
                                'required' => ['token', 'password'],
                            ],
                        ],
                    ],
                ],
            ],
        ),
        new Post(
            uriTemplate: '/user/sms_login/prepare',
            controller: PrepareSmsLoginController::class,
            openapiContext: [
                'summary' => 'Prepare sms login',
                'description' => 'Prepare sms login',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'phone' => [
                                        'type' => 'string',
                                        'description' => 'The phone of the user',
                                    ],
                                    'appSignature' => [
                                        'type' => 'string',
                                        'description' => 'The app signature of the user',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ),
        new Post(
            uriTemplate: '/user/email_login/prepare',
            controller: PrepareEmailLoginController::class,
            openapiContext: [
                'summary' => 'Prepare email login',
                'description' => 'Prepare email login',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'email' => [
                                        'type' => 'string',
                                        'description' => 'The email of the user',
                                    ],
                                    'password' => [
                                        'type' => 'string',
                                        'description' => 'The password of the user',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ),
        new Post(
            uriTemplate: '/user/login',
            controller: LoginController::class,
            openapiContext: [
                'summary' => 'User login after temp token from SMS/EMAIL',
                'description' => 'User login after temp token from SMS/EMAIL',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'token' => [
                                        'type' => 'string',
                                        'description' => 'Temporary token to authenticate user',
                                    ]
                                ],
                                'required' => ['token'],
                            ]
                        ]
                    ]
                ]
            ]
        ),
        new Post(
            uriTemplate: '/user/code/resend/{id}',
            controller: ResendCodeController::class,
            openapiContext: [
                'summary' => 'Resend code',
                'description' => 'Resend the code of the user',
            ]
        ),
        new Post(
            uriTemplate: '/user/bank/{id}',
            controller: CreateCardController::class,
            openapiContext: [
                'summary' => 'Create bank acount',
                'description' => 'Create bank acount Linkcy',
            ]
        )
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    openapiContext: [
        'tags' => ['User'],
    ]
)]
#[ApiFilter(CustomSearchUserFilter::class, properties: ['search' => 'partial'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'email' => 'exact',
        'phone' => 'exact',
        'code' => 'exact',
        'status' => ['exact', 'ipartial'],
        '_step' => ['exact', 'ipartial']
    ]
)]
#[ORM\Table(name: '`user`')]
//#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email', 'phone'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['phone'])]
//#[UniqueEntity(fields: ['email', 'phone'])]
#[UniqueEntity(fields: ['phone'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use StatusTrait;
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    #[ApiProperty(identifier: true)]
    private ?int $id = null;

    //#[ORM\Column(length: 180, unique: true, nullable: true)]
    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['user:read'])]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    #[Assert\NotNull]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    #[Groups(['user:write'])]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write', 'user:pro:write'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write', 'user:pro:write'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $code = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?bool $conditionAccepted = true;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?bool $marketingAccepted = true;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $address = null;


    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $phone = null;

    /**
     * @var Collection<int, VerificationCode>
     */
    #[ORM\OneToMany(mappedBy: 'responsible', targetEntity: VerificationCode::class, cascade: ['remove'])]
    private Collection $verificationCodes;

    #[ORM\Column(length: 45, options: ['default' => 'en'])]
    #[Groups(['user:read'])]
    private string $local = 'en';

    #[Groups(['user:write', 'user:pro:write'])]
    #[ORM\Column(length: 45, nullable: true, enumType: Step::class, options: ['default' => Step::Void])]
    private ?Step $_step;

    /**
     * @var Collection<int, Relation>
     */
    #[ORM\OneToMany(mappedBy: 'userInvited', targetEntity: Relation::class, cascade: ['remove'])]
    private Collection $relations;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $country = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['user:read', 'user:write', 'user:pro:write'])]
    private ?bool $hasWallet = false;

    public function __construct()
    {
        $this->verificationCodes = new ArrayCollection();
        $this->email = 'client_' . uniqid() . '.ozapay@mailinator.com';
        $this->relations = new ArrayCollection();
        $this->banks = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->exchanges = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->advice = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @return list<string>
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = UserConstants::ROLE_USER;

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        // Add debugging
        if ($this->password !== $password) {
            error_log('Password changed from ' . ($this->password ? 'set' : 'null') . ' to ' . ($password ? 'set' : 'null'));
            // You could also log the stack trace to see what's calling this
            error_log((new \Exception())->getTraceAsString());
        }

        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        //        $this->password = null;
        // ...add other fields to set to null
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function isConditionAccepted(): ?bool
    {
        return $this->conditionAccepted;
    }

    public function setConditionAccepted(bool $conditionAccepted): static
    {
        $this->conditionAccepted = $conditionAccepted;

        return $this;
    }

    public function isMarketingAccepted(): ?bool
    {
        return $this->marketingAccepted;
    }

    public function setMarketingAccepted(bool $marketingAccepted): static
    {
        $this->marketingAccepted = $marketingAccepted;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return Collection<int, VerificationCode>
     */
    public function getVerificationCodes(): Collection
    {
        return $this->verificationCodes;
    }

    public function addVerificationCode(VerificationCode $verificationCode): static
    {
        if (!$this->verificationCodes->contains($verificationCode)) {
            $this->verificationCodes->add($verificationCode);
            $verificationCode->setResponsible($this);
        }

        return $this;
    }

    public function removeVerificationCode(VerificationCode $verificationCode): static
    {
        if ($this->verificationCodes->removeElement($verificationCode)) {
            // set the owning side to null (unless already changed)
            if ($verificationCode->getResponsible() === $this) {
                $verificationCode->setResponsible(null);
            }
        }

        return $this;
    }

    public function getLocal(): string
    {
        return $this->local;
    }

    public function setLocal(string $local): User
    {
        $this->local = $local;
        return $this;
    }

    public function setStep(?Step $step): User
    {
        $this->_step = $step;
        return $this;
    }

    public function getStep(): ?Step
    {
        return $this->_step;
    }

    /**
     * @return Collection<int, Relation>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function addRelation(Relation $relation): static
    {
        if (!$this->relations->contains($relation)) {
            $this->relations->add($relation);
            $relation->setUserInvited($this);
        }

        return $this;
    }

    public function removeRelation(Relation $relation): static
    {
        if ($this->relations->removeElement($relation)) {
            // set the owning side to null (unless already changed)
            if ($relation->getUserInvited() === $this) {
                $relation->setUserInvited(null);
            }
        }

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): User
    {
        $this->country = $country;
        return $this;
    }

    #[Groups(['user:read'])]
    #[SerializedName('_type')]
    public function getEntityType(): string
    {
        return match (true) {
            $this instanceof Particular => 'part',
            $this instanceof Professional => 'pro',
            $this instanceof Admin => 'admin'
        };
    }

    //##### PROFESSIONAL USER INFO   #####
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'professional:read', 'user:pro:write'])]
    private ?string $denomination = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'professional:read', 'user:pro:write'])]
    private ?string $siret = null;

    public function setDenomination(?string $denomination): static
    {
        $this->denomination = $denomination;
        return $this;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;
        return $this;
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }
    //##### END PROFESSIONAL USER INFO   #####


    //##### PARTICULAR USER INFO   #####
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'particular:read', 'user:part:write'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'particular:read', 'user:part:write'])]
    private ?string $city = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'users')]
    private Collection $banks;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'banks')]
    private Collection $users;

    /**
     * @var Collection<int, Exchange>
     */
    #[ORM\OneToMany(mappedBy: 'aim', targetEntity: Exchange::class)]
    private Collection $exchanges;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(mappedBy: 'userSender', targetEntity: Transaction::class)]
    private Collection $transactions;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(mappedBy: 'consumer', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    /**
     * @var Collection<int, Advice>
     */
    #[ORM\OneToMany(mappedBy: 'advisee', targetEntity: Advice::class)]
    private Collection $advice;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $pin = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $generatedPassUpdated = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $generatedPassExpired = null;

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }
    //##### END PARTICULAR USER INFO   #####

    /**
     * @return Collection<int, self>
     */
    public function getBanks(): Collection
    {
        return $this->banks;
    }

    public function addBank(self $bank): static
    {
        if (!$this->banks->contains($bank)) {
            $this->banks->add($bank);
        }

        return $this;
    }

    public function removeBank(self $bank): static
    {
        $this->banks->removeElement($bank);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(self $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addBank($this);
        }

        return $this;
    }

    public function removeUser(self $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeBank($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Exchange>
     */
    public function getExchanges(): Collection
    {
        return $this->exchanges;
    }

    public function addExchange(Exchange $exchange): static
    {
        if (!$this->exchanges->contains($exchange)) {
            $this->exchanges->add($exchange);
            $exchange->setAim($this);
        }

        return $this;
    }

    public function removeExchange(Exchange $exchange): static
    {
        if ($this->exchanges->removeElement($exchange)) {
            // set the owning side to null (unless already changed)
            if ($exchange->getAim() === $this) {
                $exchange->setAim(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setUserSender($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getUserSender() === $this) {
                $transaction->setUserSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setConsumer($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getConsumer() === $this) {
                $subscription->setConsumer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Advice>
     */
    public function getAdvice(): Collection
    {
        return $this->advice;
    }

    public function addAdvice(Advice $advice): static
    {
        if (!$this->advice->contains($advice)) {
            $this->advice->add($advice);
            $advice->setAdvisee($this);
        }

        return $this;
    }

    public function removeAdvice(Advice $advice): static
    {
        if ($this->advice->removeElement($advice)) {
            // set the owning side to null (unless already changed)
            if ($advice->getAdvisee() === $this) {
                $advice->setAdvisee(null);
            }
        }

        return $this;
    }

    #[Groups(['user:read'])]
    public function getHasWallet(): ?bool
    {
        return $this->hasWallet;
    }

    public function setHasWallet(bool $hasWallet): static
    {
        $this->hasWallet = $hasWallet;

        return $this;
    }

    public function getPin(): ?string
    {
        return $this->pin;
    }

    public function setPin(?string $pin): static
    {
        $this->pin = $pin;

        return $this;
    }

    public function isGeneratedPassUpdated(): ?bool
    {
        return $this->generatedPassUpdated;
    }

    public function setGeneratedPassUpdated(bool $generatedPassUpdated): static
    {
        $this->generatedPassUpdated = $generatedPassUpdated;

        return $this;
    }

    public function getGeneratedPassExpired(): ?\DateTimeImmutable
    {
        return (!is_null($this->generatedPassExpired)) ? $this->generatedPassExpired : new \DateTimeImmutable();
    }

    public function setGeneratedPassExpired(?\DateTimeImmutable $generatedPassExpired): static
    {
        $this->generatedPassExpired = $generatedPassExpired;

        return $this;
    }

    public function updatePin(string $pin): void
    {
        $hashedPin = $this->dataEncryption->encrypt($pin);
        $date = new DateTimeImmutable();
        $dateTimezone = $date->setTimezone(new DateTimeZone('UTC'));
        $dateFinal = $dateTimezone->add(new DateInterval('PT30M'));

        $this->setPin((string)$hashedPin)
             ->setGeneratedPassUpdated(false)
             ->setGeneratedPassExpired($dateFinal);
    }
}
