<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/expr/v1alpha1/syntax.proto

namespace Google\Api\Expr\V1alpha1\Expr;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A comprehension expression applied to a list or map.
 * Comprehensions are not part of the core syntax, but enabled with macros.
 * A macro matches a specific call signature within a parsed AST and replaces
 * the call with an alternate AST block. Macro expansion happens at parse
 * time.
 * The following macros are supported within CEL:
 * Aggregate type macros may be applied to all elements in a list or all keys
 * in a map:
 * *  `all`, `exists`, `exists_one` -  test a predicate expression against
 *    the inputs and return `true` if the predicate is satisfied for all,
 *    any, or only one value `list.all(x, x < 10)`.
 * *  `filter` - test a predicate expression against the inputs and return
 *    the subset of elements which satisfy the predicate:
 *    `payments.filter(p, p > 1000)`.
 * *  `map` - apply an expression to all elements in the input and return the
 *    output aggregate type: `[1, 2, 3].map(i, i * i)`.
 * The `has(m.x)` macro tests whether the property `x` is present in struct
 * `m`. The semantics of this macro depend on the type of `m`. For proto2
 * messages `has(m.x)` is defined as 'defined, but not set`. For proto3, the
 * macro tests whether the property is set to its default. For map and struct
 * types, the macro tests whether the property `x` is defined on `m`.
 *
 * Generated from protobuf message <code>google.api.expr.v1alpha1.Expr.Comprehension</code>
 */
class Comprehension extends \Google\Protobuf\Internal\Message
{
    /**
     * The name of the iteration variable.
     *
     * Generated from protobuf field <code>string iter_var = 1;</code>
     */
    private $iter_var = '';
    /**
     * The range over which var iterates.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr iter_range = 2;</code>
     */
    private $iter_range = null;
    /**
     * The name of the variable used for accumulation of the result.
     *
     * Generated from protobuf field <code>string accu_var = 3;</code>
     */
    private $accu_var = '';
    /**
     * The initial value of the accumulator.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr accu_init = 4;</code>
     */
    private $accu_init = null;
    /**
     * An expression which can contain iter_var and accu_var.
     * Returns false when the result has been computed and may be used as
     * a hint to short-circuit the remainder of the comprehension.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr loop_condition = 5;</code>
     */
    private $loop_condition = null;
    /**
     * An expression which can contain iter_var and accu_var.
     * Computes the next value of accu_var.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr loop_step = 6;</code>
     */
    private $loop_step = null;
    /**
     * An expression which can contain accu_var.
     * Computes the result.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr result = 7;</code>
     */
    private $result = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $iter_var
     *           The name of the iteration variable.
     *     @type \Google\Api\Expr\V1alpha1\Expr $iter_range
     *           The range over which var iterates.
     *     @type string $accu_var
     *           The name of the variable used for accumulation of the result.
     *     @type \Google\Api\Expr\V1alpha1\Expr $accu_init
     *           The initial value of the accumulator.
     *     @type \Google\Api\Expr\V1alpha1\Expr $loop_condition
     *           An expression which can contain iter_var and accu_var.
     *           Returns false when the result has been computed and may be used as
     *           a hint to short-circuit the remainder of the comprehension.
     *     @type \Google\Api\Expr\V1alpha1\Expr $loop_step
     *           An expression which can contain iter_var and accu_var.
     *           Computes the next value of accu_var.
     *     @type \Google\Api\Expr\V1alpha1\Expr $result
     *           An expression which can contain accu_var.
     *           Computes the result.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Expr\V1Alpha1\Syntax::initOnce();
        parent::__construct($data);
    }

    /**
     * The name of the iteration variable.
     *
     * Generated from protobuf field <code>string iter_var = 1;</code>
     * @return string
     */
    public function getIterVar()
    {
        return $this->iter_var;
    }

    /**
     * The name of the iteration variable.
     *
     * Generated from protobuf field <code>string iter_var = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setIterVar($var)
    {
        GPBUtil::checkString($var, True);
        $this->iter_var = $var;

        return $this;
    }

    /**
     * The range over which var iterates.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr iter_range = 2;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getIterRange()
    {
        return $this->iter_range;
    }

    /**
     * The range over which var iterates.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr iter_range = 2;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setIterRange($var)
    {
        GPBUtil::checkMessage($var, \Google\Api\Expr\V1alpha1\Expr::class);
        $this->iter_range = $var;

        return $this;
    }

    /**
     * The name of the variable used for accumulation of the result.
     *
     * Generated from protobuf field <code>string accu_var = 3;</code>
     * @return string
     */
    public function getAccuVar()
    {
        return $this->accu_var;
    }

    /**
     * The name of the variable used for accumulation of the result.
     *
     * Generated from protobuf field <code>string accu_var = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setAccuVar($var)
    {
        GPBUtil::checkString($var, True);
        $this->accu_var = $var;

        return $this;
    }

    /**
     * The initial value of the accumulator.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr accu_init = 4;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getAccuInit()
    {
        return $this->accu_init;
    }

    /**
     * The initial value of the accumulator.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr accu_init = 4;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setAccuInit($var)
    {
        GPBUtil::checkMessage($var, \Google\Api\Expr\V1alpha1\Expr::class);
        $this->accu_init = $var;

        return $this;
    }

    /**
     * An expression which can contain iter_var and accu_var.
     * Returns false when the result has been computed and may be used as
     * a hint to short-circuit the remainder of the comprehension.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr loop_condition = 5;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getLoopCondition()
    {
        return $this->loop_condition;
    }

    /**
     * An expression which can contain iter_var and accu_var.
     * Returns false when the result has been computed and may be used as
     * a hint to short-circuit the remainder of the comprehension.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr loop_condition = 5;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setLoopCondition($var)
    {
        GPBUtil::checkMessage($var, \Google\Api\Expr\V1alpha1\Expr::class);
        $this->loop_condition = $var;

        return $this;
    }

    /**
     * An expression which can contain iter_var and accu_var.
     * Computes the next value of accu_var.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr loop_step = 6;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getLoopStep()
    {
        return $this->loop_step;
    }

    /**
     * An expression which can contain iter_var and accu_var.
     * Computes the next value of accu_var.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr loop_step = 6;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setLoopStep($var)
    {
        GPBUtil::checkMessage($var, \Google\Api\Expr\V1alpha1\Expr::class);
        $this->loop_step = $var;

        return $this;
    }

    /**
     * An expression which can contain accu_var.
     * Computes the result.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr result = 7;</code>
     * @return \Google\Api\Expr\V1alpha1\Expr
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * An expression which can contain accu_var.
     * Computes the result.
     *
     * Generated from protobuf field <code>.google.api.expr.v1alpha1.Expr result = 7;</code>
     * @param \Google\Api\Expr\V1alpha1\Expr $var
     * @return $this
     */
    public function setResult($var)
    {
        GPBUtil::checkMessage($var, \Google\Api\Expr\V1alpha1\Expr::class);
        $this->result = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Comprehension::class, \Google\Api\Expr\V1alpha1\Expr_Comprehension::class);

